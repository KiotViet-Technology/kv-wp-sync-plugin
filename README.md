## FrontEnd

    docker run --rm -it -v ./frontend:/frontend python:2 bash -c 'apt update && apt install nodejs npm -y && cd /frontend && npm i -g yarn && yarn install && yarn run build'

## BackEnd

    svn co http://plugins.svn.wordpress.org/kiotvietsync
    cd kiotvietsync
    # ...
    # svn status
    # M       trunk\frontend\dist\static\js\kiotsync.min.js
    # M       trunk\frontend\dist\static\js\kiotsync.min.js.map
    # M       trunk\includes\class-kiotviet-sync.php
    # M       trunk\kiotviet-sync.php
    # M       trunk\readme.txt
    # ...
    svn ci -m 'Reorder sync' --username mykiot --password *******
