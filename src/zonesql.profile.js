var profile = (function(){
    return {
        basePath: ".",
        releaseDir: "../",
        releaseName: "dist",
        action: "release",
        layerOptimize: "closure",
        optimize: "closure",
        cssOptimize: "comments",
        mini: true,
        stripConsole: "warn",
        selectorEngine: "lite",

        defaultConfig: {
            hasCache:{
                "dojo-built": 1,
                "dojo-loader": 1,
                "dom": 1,
                "host-browser": 1,
                "config-selectorEngine": "lite"
            },
            async: 1
        },
		
        packages:[{
            name: "dojo",
            location: "dojo"
        },{
            name: "dijit",
            location: "dijit"
        },{
            name: "dstore",
            location: "dstore"
        },{
            name: "dgrid",
            location: "dgrid"
        },{
            name: "flat",
            location: "flat"
        },{
            name: "zonesql",
            location: "zonesql"
        }],
		
        layers: {
            "dojo/dojo": {
			
                include: [ 
					"dojo/_base/lang",
					"dojo/dom", 
					"dojo/dom-class",
					"dojo/on", 
					"dojo/parser", 
					"dijit/registry", 
					"dojo/request/xhr", 
					"dojo/store/JsonRest", 
					"dijit/Tree", 
					"dijit/tree/ObjectStoreModel", 
					"dojo/_base/declare", 
					"dstore/Trackable",
					"dstore/Cache",
					"dstore/Request",
					"dgrid/OnDemandGrid",
					"dgrid/extensions/ColumnResizer",
					"dojo/request",
					"dijit/layout/ContentPane", 
					"dijit/layout/BorderContainer", 
					"dijit/layout/TabContainer", 
					"dijit/Dialog",
					"dijit/form/Button",
					"dijit/form/TextBox",
					"dijit/form/Select",
					"dijit/form/Form",
					"dijit/form/ValidationTextBox",
					"dojo/domReady"				
				],
                customBase: true,
                boot: true
            }			
		}
    };
})();