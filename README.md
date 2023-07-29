# CustomTNT-PM5

## Description
CustomTNT is a plugin for Pocketmine that adds customizable TNT to your server. This plugin relies on the Customies plugin for some functionalities.

## Configuration

### `config.yml`

#### Custom Name
- Type: String
- Default: "Custom TNT"
- Description: The custom name for the TNT. It is recommended not to change the name after the first world load to avoid world corruption.

#### Explosion Radius
- Type: Integer
- Default: 10
- Description: The explosion radius of the custom TNT. Larger explosions can impact your server's performance.

#### Time Before Explode
- Type: Integer
- Default: 10
- Description: The time in seconds before the custom TNT explodes after being placed.

#### Works Underwater
- Type: Boolean
- Default: true
- Description: Determines whether the custom TNT works underwater or not.

#### Time Visible
- Type: Boolean
- Default: true
- Description: Set to `false` if you don't want to see the time above the TNT.

#### Time Format
- Type: String
- Default: "§g{time}"
- Description: The format for displaying the time above the TNT. You can use color codes.

#### Textures
- Description: Define the custom textures for the TNT.
- Geometry: Type: String, Default: "", Description: Leave blank if you don't use custom geometry.
- Up: Type: String, Default: custom_tnt_top, Description: The top texture name entered in the pack (terrain_texture.json).
- Down: Type: String, Default: custom_tnt_bottom, Description: The bottom texture name entered in the pack (terrain_texture.json).
- North, South, East, West: Type: String, Default: custom_tnt_side, Description: The side texture names entered in the pack (terrain_texture.json) for different directions.

## Dependencies
This plugin depends on the Customies plugin. Make sure to have it installed and loaded before using CustomTNT.

## How to Use
1. Install the Customies plugin.
2. Place the CustomTNT-PM5 plugin in your Pocketmine plugins folder.
3. Edit the `config.yml` to customize the properties of the TNT.
4. Restart your Pocketmine server.
5. Players can now place the custom TNT with the specified properties in the game.

## Support and Issues
If you encounter any issues or need support regarding the CustomTNT-PM5 plugin, feel free to create an issue on the GitHub repository for this project.

Happy playing!

(Thanks ChatGPT for this doc 😂)
