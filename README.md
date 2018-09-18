
# SC Live

## [REQUIREMENTS]

- Vagrant (https://www.vagrantup.com/docs/installation/)
- You must have two-factor authentication switched on your Instagram account

## [TO INSTALL]

1) Git clone to a directory
2) Navigate to that directory and run 'Vagrant Up'
3) SSH into the newly created Vagrant Box and navigate to '/var/www/public'
4) Run 'Composer Install'

## [TO RUN]

1) Run 'php scLive setup' followed by your username and password
2) Run 'php scLive login'
3) Run 'php scLive stream:start'

## [COMMANDS WHEN LIVE]

-stop : Stops the stream
-pin : Add to the end of a comment to pin
-unpin : Unpins any pinned comments
