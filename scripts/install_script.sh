#!/bin/bash

function owcli-install() {
    return_value=0
    if [[ "/usr/local/bin/owcli" != `find /usr/local/bin/ -type l -name owcli` ]]
    then
        cd install;
        mkdir owcli;
        cd owcli
        wget --no-check-certificate https://github.com/AKSW/owcli/tarball/master -O owcli.tar.gz;
        tar xzf owcli.tar.gz;
        folder=`find -type d -name A*`;
        cd $folder;
        sudo make install;
        cd ../..;
        pwd;
        cp -n .owcli ~/.owcli;
        nano ~/.owcli;
        rm -rf owcli;
        
        # install raptor
        sudo apt-get install raptor-utils;
        
        # install pear and other PHP utils
        sudo apt-get install php5-curl;
        sudo apt-get install php-pear;
        sudo pear install Console_Getargs;
        sudo pear install Console_Table;
    else
        echo " owcli already installed.";
        return_value=1
    fi
    return $return_value;
}

function kb-install() {
    echo " Models to drop: "
    echo "  http://www.dispedia.de/"
    echo "  http://als.dispedia.de/"
    echo "  http://patients.dispedia.de/"
    echo "  http://als.dispedia.de/frs/"
    echo -n " Do you really want to drop all this models and create new ones? (y/n): "
    read CONFIRM;
    if [[ $CONFIRM == "y" ]]
    then
        owcli -m http://www.dispedia.de/ -e model:drop;
        owcli -m http://als.dispedia.de/ -e model:drop;
        owcli -m http://patients.dispedia.de/ -e model:drop;
        owcli -m http://als.dispedia.de/frs/ -e model:drop;

        owcli -m http://www.dispedia.de/ -e model:create
        owcli -m http://www.dispedia.de/ -e model:add -i ontologies/dispediaCore.owl
        owcli -m http://www.dispedia.de/ -e model:add -i ontologies/wrapperAlsfrs.owl
        owcli -m http://www.dispedia.de/ -e model:add -i ontologies/wrapperIcd.owl
        owcli -m http://www.dispedia.de/ -e model:add -i ontologies/wrapperIcf.owl

        owcli -m http://als.dispedia.de/ -e model:create
        owcli -m http://als.dispedia.de/ -e model:add -i ontologies/ekbProposal.owl

        owcli -m http://patients.dispedia.de/ -e model:create
        owcli -m http://patients.dispedia.de/ -e model:add -i ontologies/patients.owl
        owcli -m http://patients.dispedia.de/ -e model:add -i ontologies/jonDoes.owl

        owcli -m http://als.dispedia.de/frs/ -e model:create
        owcli -m http://als.dispedia.de/frs/ -e model:add -i ontologies/alsfrs.rdf
    else
        echo " Nothing chanced";
    fi
}

# this loop parse the paramter for this script
while getopts ":ik" optname
    do
        case "$optname" in
        "i")
            owcli-install;
        ;;
        "k")
            kb-install;
        ;;
        "?")
            echo "Unknown option $OPTARG"
        ;;
        ":")
            echo "No argument value for option $OPTARG"
        ;;
        *)
            # Should not occur
            echo "Unknown error while processing options"
        ;;
    esac
done

exit 0;
