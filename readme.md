# Lokale installatie

Clone de wordpress plugin

    git clone git@bitbucket.org:forwarder/belco-woocommerce.git

Clone de javascript src

    git clone git@bitbucket.org:forwarder/belco-client.git
    
Plugin installeren, hiervoor maken we een symlink naar de repo

    cd wordpress/wp-content/plugins
    ln -s /path/to/belco-woocommerce belco

Javascript dist koppelen

    rm belco-woocommerce/js/belco.js
    ln -s ../../belco-client/dist/belco.js belco-woocommerce/js/belco.js
    