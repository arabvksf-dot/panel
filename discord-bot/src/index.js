import { createHmac, randomUUID } from 'node:crypto';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { config as loadEnv } from 'dotenv';
import {
    Client,
    GatewayIntentBits,
    REST,
    Routes,
    SlashCommandBuilder,
} from 'discord.js';

loadEnv({ path: resolve(dirname(fileURLToPath(import.meta.url)), '../.env') });

const required = ['DISCORD_BOT_TOKEN', 'DISCORD_APP_ID', 'DISCORD_GUILD_ID', 'PANEL_INTERNAL_URL', 'PANEL_DISCORD_SECRET'];
const missing = required.filter((key) => !process.env[key]);
if (missing.length) {
    throw new Error(`Missing required configuration: ${missing.join(', ')}`);
}

const panelEndpoint = new URL(process.env.PANEL_INTERNAL_URL);
if (panelEndpoint.protocol !== 'https:') {
    throw new Error('PANEL_INTERNAL_URL must use HTTPS to protect the request signature.');
}

const command = new SlashCommandBuilder()
    .setName('hosting-status')
    .setDescription('Check your linked hosting status and sync your server role.');

const rest = new REST({ version: '10' }).setToken(process.env.DISCORD_BOT_TOKEN);
await rest.put(Routes.applicationGuildCommands(process.env.DISCORD_APP_ID, process.env.DISCORD_GUILD_ID), {
    body: [command.toJSON()],
});

const client = new Client({ intents: [GatewayIntentBits.Guilds] });

async function fetchHostingStatus(discordId) {
    const body = JSON.stringify({ discord_id: discordId });
    const timestamp = Math.floor(Date.now() / 1000).toString();
    const nonce = randomUUID();
    const message = [timestamp, nonce, 'POST', panelEndpoint.pathname, body].join('.');
    const signature = createHmac('sha256', process.env.PANEL_DISCORD_SECRET).update(message).digest('hex');

    const response = await fetch(panelEndpoint, {
        method: 'POST',
        redirect: 'error',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Discord-Timestamp': timestamp,
            'X-Discord-Nonce': nonce,
            'X-Discord-Signature': signature,
        },
        body,
        signal: AbortSignal.timeout(10_000),
    });

    if (!response.ok) {
        throw new Error(`Panel returned HTTP ${response.status}`);
    }

    const result = await response.json();
    if (typeof result.linked !== 'boolean' || typeof result.has_active_hosting !== 'boolean') {
        throw new Error('Panel returned an invalid hosting status response');
    }

    return result;
}

async function syncMemberRole(member, hasHosting) {
    const hostingRoleId = process.env.DISCORD_HOSTING_ROLE_ID;
    const noHostingRoleId = process.env.DISCORD_NO_HOSTING_ROLE_ID;
    if (!hostingRoleId && !noHostingRoleId) return;

    if (hasHosting && hostingRoleId) await member.roles.add(hostingRoleId);
    if (hasHosting && noHostingRoleId) await member.roles.remove(noHostingRoleId);
    if (!hasHosting && noHostingRoleId) await member.roles.add(noHostingRoleId);
    if (!hasHosting && hostingRoleId) await member.roles.remove(hostingRoleId);
}

client.on('interactionCreate', async (interaction) => {
    if (!interaction.isChatInputCommand() || interaction.commandName !== 'hosting-status') return;

    await interaction.deferReply({ ephemeral: true });
    try {
        const result = await fetchHostingStatus(interaction.user.id);
        if (!result.linked) {
            await interaction.editReply('Your Discord account is not linked. Sign in to the panel and connect Discord in account settings.');
            return;
        }

        if (interaction.inGuild() && (process.env.DISCORD_HOSTING_ROLE_ID || process.env.DISCORD_NO_HOSTING_ROLE_ID)) {
            const member = await interaction.guild.members.fetch(interaction.user.id);
            await syncMemberRole(member, result.has_active_hosting);
        }

        await interaction.editReply(
            result.has_active_hosting
                ? 'Your account has active hosting. Your Discord roles have been synchronized.'
                : 'Your account has no active hosting. Your Discord roles have been synchronized.'
        );
    } catch (error) {
        console.error('Hosting status request failed:', error instanceof Error ? error.message : 'unknown error');
        await interaction.editReply('The panel could not verify your hosting status. Please try again later.');
    }
});

client.once('ready', () => {
    console.log(`Discord bot ready as ${client.user.tag}`);
});

await client.login(process.env.DISCORD_BOT_TOKEN);