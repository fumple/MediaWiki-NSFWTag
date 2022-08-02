# MediaWiki Extension - NSFWTag
This extension adds parser tags and a function, to hide NSFW (or really any) content from users.<br/>
(`View source / Edit` is unaffected by this)
## Features
- Adds two parser tags: `<sfw></sfw>` and `<nsfw></nsfw>`
- Adds a parser function: `{{#nsfw: nsfw text | sfw text}}`
- Adds 2 settings: `Show NSFW content` under `Appearance` and `Show NSFW toggle when editing a page` under `Editing`
- Adds a new toggle when editing a page, that can be used to toggle NSFW tags when previewing without going to preferences
- Adds a query param: `?shownsfw=1` that can be used to force on/off NSFW tags 
- Wraps SFW and NSFW content with a span.sfwtag or span.nsfwtag respectively
## Usage
- `<sfw>text</sfw>` for SFW content, everything between the tags will be shown to users with the `Show NSFW content` setting disabled.
- `<nsfw>text</nsfw>` for NSFW content, everything between the tags will be shown to users with the `Show NSFW content` setting enabled.
- `{{#nsfw: nsfw text | sfw text}}` is the two above tags combined into one, should be self-explanatory
- `MediaWiki:nsfwtag-header` and `MediaWiki:nsfwtag-footer` will be shown on pages which use the tags / functions above the page content and below the page content respectively
## Installation
- Download the newest release
- Put it in the extensions folder
- Add `wfLoadExtension("NSFWTag");` to `LocalSettings.php`
## Tested versions
Using phan: 1.35.6, 1.35.7, 1.37.3, 1.38.2
By me: 1.35.6 and 1.38.1