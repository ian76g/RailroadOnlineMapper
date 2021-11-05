# RailroadOnlineMapper

[@ian76g#6577](https://discordapp.com/users/306158630145753090)

Currently hosted on https://minizwerg.online/

## Getting Started

### Run locally

#### PHP Setup

1. Ensure you have PHP (v<8) installed, or [download here](https://windows.php.net/downloads/releases/php-7.4.25-Win32-vc15-x64.zip)

2. Rename `php.ini.development` to `php.ini`
3. Set the extension_dir of `php.ini` to `ext`

   > `extension_dir = "ext"`

4. in ini file enable extensions mb
   > `extension=mbstring`

#### Project setup

5. Clone this repo to your local machine
6. Copy your save file from `%localappdata%/arr/Saved/SaveGames` (or `./setup`) to the root of the project (eg. `./slot1.sav`)
7. Run the convertor
   > `php converter.php slot1.sav`
8. Find output html in `./done`

### Host on webserver

1. Copy everything to a folder of your webserver
2. Done

### Output

`xx.json`
The save in nice readable JSON

`db.db`
A database used for hosting with filename, user, switches, trains, etc.

## Contributers

[@JKD#0205](https://discordapp.com/users/905751614357372938)
