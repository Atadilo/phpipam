<?php

// required functions
if (! is_object(@$User)) {
    require (dirname(__FILE__) . '/../../../functions/functions.php');
    // classes
    $Database = new Database_PDO();
    $User = new User($Database);

    // DTO class
    $Sections = new Sections($Database);
    $Subnets = new Subnets($Database);
    $Addresses = new Addresses($Database);

    // fetch all
    $sections = $Sections->fetch_all_sections();
    $subnets = $Subnets->fetch_all_subnets();
    $devices = $User->fetch_all_objects("devices", "hostname");
}
// user must be authenticated
$User->check_user_session();
?>

<!-- local CSS -->
<style type="text/css">
#mynetwork {
	width: 100%;
	height: 600px;
	border: 1px solid lightgray;
}
</style>

<script type="text/javascript">

	var sections = [];
    var devices = [];
    var subnets = [];
    
    <?php
    # Complete subnets array with list of connected Divices
    foreach ($subnets as &$s) {
        $s = (array) $s;
        if ($s['subnet'] > 0) {
            $ips = $Addresses->fetch_subnet_addresses($s['id']);
            foreach ($ips as $ip) {
                $ip = (array)$ip;
                if (! empty($ip['switch'] )) {
                    $s['switch'][] = [
                        'id' => $ip['switch'],
                        'ip_addr' => $ip['ip_addr']
                    ];
                }
            }
        }
    }
    
    # write as json
    print "sections = " . json_encode($sections) . ";" . PHP_EOL;
    print "devices = " . json_encode($devices) . ";" . PHP_EOL;
    print "subnets = " . json_encode($subnets) . ";" . PHP_EOL;
    ?>
    
    //console.log("devices", devices);
    //console.log("subnets", subnets);

    var sectionsGroups = {};
    var nodes = null;
    var edges = null;
    var network = null;

    // defaults group colors issue from https://github.com/almende/vis/blob/master/lib/network/modules/Groups.js
	var defaultGroups = [
        {border: "#2B7CE9", background: "#97C2FC", highlight: {border: "#2B7CE9", background: "#D2E5FF"}, hover: {border: "#2B7CE9", background: "#D2E5FF"}}, // 0: blue
        {border: "#FFA500", background: "#FFFF00", highlight: {border: "#FFA500", background: "#FFFFA3"}, hover: {border: "#FFA500", background: "#FFFFA3"}}, // 1: yellow
        {border: "#FA0A10", background: "#FB7E81", highlight: {border: "#FA0A10", background: "#FFAFB1"}, hover: {border: "#FA0A10", background: "#FFAFB1"}}, // 2: red
        {border: "#41A906", background: "#7BE141", highlight: {border: "#41A906", background: "#A1EC76"}, hover: {border: "#41A906", background: "#A1EC76"}}, // 3: green
        {border: "#E129F0", background: "#EB7DF4", highlight: {border: "#E129F0", background: "#F0B3F5"}, hover: {border: "#E129F0", background: "#F0B3F5"}}, // 4: magenta
        {border: "#7C29F0", background: "#AD85E4", highlight: {border: "#7C29F0", background: "#D3BDF0"}, hover: {border: "#7C29F0", background: "#D3BDF0"}}, // 5: purple
        {border: "#C37F00", background: "#FFA807", highlight: {border: "#C37F00", background: "#FFCA66"}, hover: {border: "#C37F00", background: "#FFCA66"}}, // 6: orange
        {border: "#4220FB", background: "#6E6EFD", highlight: {border: "#4220FB", background: "#9B9BFD"}, hover: {border: "#4220FB", background: "#9B9BFD"}}, // 7: darkblue
        {border: "#FD5A77", background: "#FFC0CB", highlight: {border: "#FD5A77", background: "#FFD1D9"}, hover: {border: "#FD5A77", background: "#FFD1D9"}}, // 8: pink
        {border: "#4AD63A", background: "#C2FABC", highlight: {border: "#4AD63A", background: "#E6FFE3"}, hover: {border: "#4AD63A", background: "#E6FFE3"}}, // 9: mint

        {border: "#990000", background: "#EE0000", highlight: {border: "#BB0000", background: "#FF3333"}, hover: {border: "#BB0000", background: "#FF3333"}}, // 10:bright red

        {border: "#FF6000", background: "#FF6000", highlight: {border: "#FF6000", background: "#FF6000"}, hover: {border: "#FF6000", background: "#FF6000"}}, // 12: real orange
        {border: "#97C2FC", background: "#2B7CE9", highlight: {border: "#D2E5FF", background: "#2B7CE9"}, hover: {border: "#D2E5FF", background: "#2B7CE9"}}, // 13: blue
        {border: "#399605", background: "#255C03", highlight: {border: "#399605", background: "#255C03"}, hover: {border: "#399605", background: "#255C03"}}, // 14: green
        {border: "#B70054", background: "#FF007E", highlight: {border: "#B70054", background: "#FF007E"}, hover: {border: "#B70054", background: "#FF007E"}}, // 15: magenta
        {border: "#AD85E4", background: "#7C29F0", highlight: {border: "#D3BDF0", background: "#7C29F0"}, hover: {border: "#D3BDF0", background: "#7C29F0"}}, // 16: purple
        {border: "#4557FA", background: "#000EA1", highlight: {border: "#6E6EFD", background: "#000EA1"}, hover: {border: "#6E6EFD", background: "#000EA1"}}, // 17: darkblue
        {border: "#FFC0CB", background: "#FD5A77", highlight: {border: "#FFD1D9", background: "#FD5A77"}, hover: {border: "#FFD1D9", background: "#FD5A77"}}, // 18: pink
        {border: "#C2FABC", background: "#74D66A", highlight: {border: "#E6FFE3", background: "#74D66A"}, hover: {border: "#E6FFE3", background: "#74D66A"}}, // 19: mint

        {border: "#EE0000", background: "#990000", highlight: {border: "#FF3333", background: "#BB0000"}, hover: {border: "#FF3333", background: "#BB0000"}} // 20:bright red
      ];
    // prepare options.groups of Vis Network
    var index = 0;
    sections.forEach ( function (section) {            
    	sectionsGroups['id' + section['id']] = {
                shape: 'icon',
                color: defaultGroups[index % 20],
                icon: {
                  code: '\uf0e8',
                  color: defaultGroups[index % 20]['background']
                }
              };
    	index +=1;
    });
    
	function createDeviceNode(device) {
				
        return {
        	'id': 'd' + device['id'], 
    		'label' : device['hostname'],
    		'shape': 'box',
        }
	}
	
	function createSubnetNode(subnet) {

		label = long2ip(subnet['subnet']) + "/" + subnet['mask'];
		
		desc = "<u>Subnet :</u> <br>";
		if ( subnet['description'] != null ) {
			desc += "<li>" + subnet['description'] + "<br>";				
		}
		desc += "<li>" + label;
		
        return {
        	'id': 's' + subnet['id'], 
    		'label' : label,
    		'title' : desc,
    		//'shape': 'icon',
    		'group': 'id' + subnet['sectionId'], 
    		//'icon': {
        	//	'code': "\uf0e8",
        	//	'color': null
    		//},
        }
	}

	function long2ip (ip) {
		 return [ip >>> 24, ip >>> 16 & 0xFF, ip >>> 8 & 0xFF, ip & 0xFF].join('.');
			
	}
	
    // Called when the Visualization API is loaded.
    function draw() {

    	// Create a data table with sections
    	groups = [];
        // Create a data table with nodes.
        nodes = [];
        // Create a data table with links.
        edges = [];

        devices.forEach ( function (d) {            
         	nodes.push(createDeviceNode(d));
        });

        subnets.forEach ( function (s) {
            if (s['subnet'] > 0 ) {

             	nodes.push(createSubnetNode(s));

             	if ( 'switch' in s) {
             		s['switch'].forEach( function (ip) {
                 		var e = {
                         		'from': 's' + s['id'], 
                         		'to': 'd' + ip['id'], 
                         		'label': long2ip(ip['ip_addr'])
                 		};
                 	
                 		edges.push(e);
             	});
            }
        }});
        
        // create a network
        var container = document.getElementById('mynetwork');
        var data = {
            nodes: nodes,
            edges: edges,
        };
        var options = {
            "physics": {
                 "barnesHut": {
                      "gravitationalConstant": -5000
                 }
            },            
            groups: sectionsGroups
        }
        network = new vis.Network(container, data, options);
    }
</script>

<div id="mynetwork"></div>


<script type="text/javascript">
	draw();
</script>
