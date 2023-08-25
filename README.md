# HashCash

A stock exchange game inspired by HSX.com, Hash Exchange (or Hashex) uses current popularity in hashtags as measurement of current "value" of in game currency.

Players can begin research of a hashtag if it is not available and then either buy long or short stock in that hashtag.

#### FYI

Currently, the .htaccess file should direct all requests back to index.php in the root. This makes it behave like a router.

TODO: Better UI using proper back end structure and a not faked router.

#### Setup

For now project will need hashcash.sql imported for schema before anything will run.

TODO: Make this into a migration.

#### Usage

The UI is kept simple so most of the fun stuff happens in a sudo-CRON file that scraps twitter and all other time related tasks. 

This is under cron/run.php and can be run with a simple `php run.php` from that folder.

TODO: Uhh actually this all works pretty good right now. But it's maintenance hell.

#### Production

Develop branch is running at http://hashex.ca (no longer available)

TODO: Pipeline for master to push here automatically!

#### Long Term

Road map includes things like
- Lottery: Take a chance at winning (static price or maybe dynamic % of all trades last 24 hrs?) dollars.
- Boosts: Something like buy other things like an additional research point or the one off ability to invest in a stock at past value