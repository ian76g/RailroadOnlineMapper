# RailroadOnlineMapper

Two options to set this up

# run locally
- get/download PHP (v<8)
- rename php.ini.development to php.ini
- in ini file set extension_dir to ext
- in ini file enable extensions gd and mb
- download this repo content in a local folder
- copy your save from %localappdata%/arr/Saved/SaveGames to your local folder (eg. slot1.sav)
- php converter.php slot1.sav
- done (output in done folder)


# host on webserver
- copy everything to a folder of your webserver
- done
