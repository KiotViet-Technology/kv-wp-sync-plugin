# KiotViet Sync Plugin

Plugin that supports synchronization of products and orders between WordPress website and KiotViet Retail.

## Disclaimer

This is an open source plugin that supports synchronization with KiotViet Retail. The plugin is provided "as is" without warranty of any kind, express or implied, including but not limited to the warranties of merchantability, fitness for a particular purpose and noninfringement. In no event shall the authors or copyright holders be liable for any claim, damages or other liability, whether in an action of contract, tort or otherwise, arising from, out of or in connection with the plugin or the use or other dealings in the plugin.

## Contributing

This is an open source project and we welcome contributions! If you would like to contribute to this plugin, please feel free to submit pull requests, report issues, or suggest improvements. Your contributions help make this plugin better for everyone.

## Development

### FrontEnd

    docker run --rm -it -v ./frontend:/frontend python:2 bash -c 'apt update && apt install nodejs npm -y && cd /frontend && npm i -g yarn && yarn install && yarn run build'

### BackEnd

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
