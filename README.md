# RailroadOnlineMapper

Two options to set this up

# run locally
- get/download PHP (v<8)

https://windows.php.net/downloads/releases/php-7.4.25-Win32-vc15-x64.zip

- rename php.ini.development to php.ini
- in ini file set extension_dir to ext
  
extension_dir = "ext"

- in ini file enable extensions mb

extension=mbstring

- download this repo content in a local folder
- copy your save from %localappdata%/arr/Saved/SaveGames to your local folder (eg. slot1.sav)
- php converter.php slot1.sav
- done (output in done folder)


# host on webserver
- copy everything to a folder of your webserver
- done
