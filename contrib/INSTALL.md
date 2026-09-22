# Installation Guide

## Prerequisites

1. **phpBB 3.3.0+** installed and working
2. **bbGuild core** (`avathar/bbguild`) installed and enabled
3. **PHP 7.4+** with cURL and GD extensions

## Step 1: Install the Extension

1. Download or clone `bbguildwow` into your phpBB extensions directory:
   ```
   /ext/avathar/bbguildwow/
   ```
2. Verify the file structure — you should have:
   ```
   ext/avathar/bbguildwow/
   ├── composer.json
   ├── ext.php
   ├── config/services.yml
   ├── game/
   │   ├── wow_provider.php
   │   ├── wow_installer.php
   │   └── wow_api.php
   └── api/
       ├── battlenet.php
       ├── battlenet_resource.php
       ├── battlenet_character.php
       ├── battlenet_guild.php
       ├── battlenet_realm.php
       └── battlenet_achievement.php
   ```

3. Navigate to **ACP > Customise > Manage extensions**.
4. Find **bbGuild - World of Warcraft** under Disabled Extensions.
5. Click **Enable**.

If the extension does not appear, check that bbGuild core is enabled first. The WoW extension requires it.

## Step 2: Install the WoW Game

1. Navigate to **ACP > bbGuild > Games**.
2. World of Warcraft should now appear in the list of installable games.
3. Click **Install** next to World of Warcraft.
4. This populates the database with WoW factions, classes, races, and roles.

## Step 3: Configure Battle.net API (Optional)

The Battle.net API enables automatic guild member synchronization, character portraits, and armory links. It is optional — you can manage your roster manually without it.

### Obtaining API Credentials

1. Go to [https://develop.battle.net/access/clients](https://develop.battle.net/access/clients)
2. Log in with your Battle.net account (a free account is sufficient — no game purchase required)
3. Create a new API client (enter `http://localhost` as redirect URI)
4. Copy your **Client ID** and **Client Secret** (the secret is shown only once)

### Entering Credentials in bbGuild

1. Navigate to **ACP > bbGuild > Games**.
2. Click **Edit** next to World of Warcraft.
3. Enter your **Client ID** and **Client Secret**.
4. Select your **Locale** (determines the language of API responses).
5. Save.

### Available Locales by Region

| Region | Locales |
|--------|---------|
| US | en_US, es_MX, pt_BR |
| EU | en_GB, de_DE, es_ES, fr_FR, it_IT, pl_PL, pt_PT, ru_RU |
| KR | ko_KR |
| TW | zh_TW |

## Step 4: Create a Guild

1. Navigate to **ACP > bbGuild > Guilds**.
2. Click **Add Guild**.
3. Fill in guild name, realm, and region.
4. If you have API credentials configured, click **Update from Armory** to pull guild data automatically.

## Step 5: Verify

1. Visit your forum's bbGuild page (usually `/guild/welcome/1`).
2. You should see your guild with the Alliance or Horde theme applied.
3. Navigate to the roster tab to see your guild members.

## Troubleshooting

### Extension does not appear in ACP
- Verify bbGuild core is enabled first.
- Check that files are in the correct directory: `ext/avathar/bbguildwow/composer.json` must exist.
- Clear the phpBB cache: ACP > General > Purge the cache.

### "World of Warcraft" does not appear in installable games
- Make sure you enabled the extension (Step 1), not just copied the files.
- Purge the phpBB cache and reload.

### API calls fail
- Verify your API key is correct.
- Check that your region and locale match.
- See [BATTLENET_API.md](BATTLENET_API.md) for known API issues and error codes.

### Class/race images not showing
- Images currently reside in the bbGuild core extension at `ext/avathar/bbguild/images/`.
- Verify bbGuild core is enabled and its images directory is intact.
