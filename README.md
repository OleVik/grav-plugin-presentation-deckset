# Presentation Deckset Plugin

The **Presentation Deckset** Plugin is for [Grav CMS](http://github.com/getgrav/grav), and allows you to use Deckset Syntax with the [Presentation Plugin](https://github.com/OleVik/grav-plugin-presentation). The Presentation Plugin must be installed for this plugin to run.

## Installation

Installing the Presentation Plugin Deckset plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install presentation-deckset

This will install the Presentation Plugin Deckset plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/presentation-deckset`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `presentation-deckset`. You can find these files on [GitHub](https://github.com/OleVik/grav-plugin-presentation-deckset) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/presentation-deckset
	
> NOTE: This plugin is a modular component for Grav which requires [Grav](http://github.com/getgrav/grav) and the [Error](https://github.com/getgrav/grav-plugin-error) and [Problems](https://github.com/getgrav/grav-plugin-problems) to operate.

### Admin Plugin

If you use the admin plugin, you can install directly through the admin plugin by browsing the `Plugins` tab and clicking on the `Add` button.

## Configuration

Before configuring this plugin, you should copy the `user/plugins/presentation-deckset/presentation-deckset.yaml` to `user/config/plugins/presentation-deckset.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
```

Note that if you use the admin plugin, a file with your configuration, and named presentation-deckset.yaml will be saved in the `user/config/plugins/` folder once the configuration is saved in the admin.

## Usage

With the Presentation Deckset Plugin enabled, add the following to the Presentation Plugin's configuration:

```yaml
parser: 'presentation-deckset/API/Parser.php'
```

This will replace the Presentation Plugin's Parser with the Deckset-compliant one that this Plugin enables.

### Syntax restrictions

The [Deckset Syntax](https://docs.deckset.com/English.lproj/Customization/02-custom-theming.html) for Customization Commands is emulated through the Parser, with some exceptions:

- Table-commands are not emulated
- Footnote-commands are not emulated
- Formula-commands are not emulated

Their effects are largely suppressed because of themes provided by Reveal.js, and they can more easily enhanced by other plugins. If there are other inadequacies, this is likely due to the lack of documentation provided by Deckset or lack of an equivalent HTML-element. If you find any areas for improvement, feel free to create a Pull Request.