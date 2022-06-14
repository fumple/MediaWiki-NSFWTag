# MediaWiki Extension - NSFWTag
This extension adds parser tags and a function, to hide NSFW content from users.<br>
(`View source / Edit` is unaffected by this)
## Features
- Adds two parser tags: `<sfw></sfw>` and `<nsfw></nsfw>`
- Adds a parser function: `{{#nsfw: nsfw text | sfw text}}`
- Adds a setting: `Show NSFW content` under `Appearance`
- Wraps SFW and NSFW content with a span.sfw or span.nsfw respectively
## Usage
- `<sfw>text</sfw>` for SFW content, everything between the tags will be shown to users with the `Show NSFW content` setting disabled.
- `<nsfw>text</nsfw>` for NSFW content, everything between the tags will be shown to users with the `Show NSFW content` setting disabled.
- `{{#nsfw: nsfw text | sfw text}}` is the two above tags combined into one, should be self-explanatory
- `MediaWiki:nsfwtag-header` and `MediaWiki:nsfwtag-footer` will be shown on pages which use the tags / functions above the page content and below the page content respectively
## Configuration
There are currently no configurable settings
## Installation
- Download the newest release
- Put it in the extensions folder
- Add `wfLoadExtension("NSFWTag");` to `LocalSettings.php`