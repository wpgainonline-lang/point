# Primecutss MCP (WordPress plugin)

Adds an MCP endpoint at `https://YOUR-SITE/wp-json/pcmcp/v1/mcp` so an AI assistant can manage the site.

## Install
1. Zip the `primecutss-mcp` folder, upload via Plugins → Add New → Upload, activate.
2. Settings → Primecutss MCP → **Generate new token** (shown once).
3. Optional: tick **Allow file writes** to let the assistant edit theme/plugin files.

## Connect (Claude Code)
    claude mcp add --transport http primecutss https://YOUR-SITE/wp-json/pcmcp/v1/mcp \
      --header "Authorization: Bearer YOUR_TOKEN"

If your host strips the Authorization header, use `--header "X-PCMCP-Token: YOUR_TOKEN"`.

## Tools
site_info, list/get/create/update/trash_post, list_menus, get/update_custom_css, get/update_option,
list/toggle_plugin, list/switch_theme, list_files, read_file, write_file, replace_in_file,
list_backups, restore_backup, clear_cache, get_log.

## Safety
Token stored hashed; file access sandboxed to wp-content; file writes off by default; PHP syntax-checked
and backed up before every write; core options (siteurl, admin_email, …) are protected.
