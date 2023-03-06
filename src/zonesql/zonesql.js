/** 
 * ZoneSQL - JavaScript File. 
 *
 * @package ZoneSQL
 * @author Adam Tandowski
 * @version 1.0
 */

// Loads in Dojo modules and other apps using AMD
require(

    [
	"dojo/_base/lang",
    "dojo/dom", 
	"dojo/dom-class",
	"dojo/dom-style",
    "dojo/on", 
	"dojo/aspect",
    "dojo/parser", 
    "dijit/registry", 
    "dojo/request/xhr", 
    "dojo/store/JsonRest", 
    "dijit/Tree", 
    "dijit/tree/ObjectStoreModel", 
    "dojo/_base/declare", 
    //"ace/ace",
	"dstore/Trackable",
	"dstore/Cache",
	"dstore/Request",
	"dgrid/OnDemandGrid",
	"dgrid/extensions/ColumnResizer",
	'dojo/request',

    "dijit/layout/ContentPane", 
    "dijit/layout/BorderContainer", 
    "dijit/layout/TabContainer", 
    "dijit/Dialog",
    "dijit/form/Button",
    "dijit/form/TextBox",
	"dijit/form/Select",

    "dojo/domReady!"
    ], 

function(lang, dom, domClass, domStyle, on, aspect, parser, registry, xhr, JsonRest, Tree, ObjectStoreModel, declare, Trackable, Cache, Request, OnDemandGrid, ColumnResizer, request /*, aspect */) {

    var sql = ace.edit("sql");
    sql.getSession().setMode("ace/mode/sql");
    sql.setOptions({
        fontFamily: "courier"
    });

    parser.parse();

	if(zoneConfig.auth == 'none') 
		registry.byId('logout').domNode.style.display = 'none';
	
    setTimeout(function() { dom.byId('loadingOverlay').style.display = 'none'; }, 500);

	// Handle sizing of Ace Editor (css style alone has some issues)
	var resizeAce = function() {
		domStyle.set('sql', 'height', dom.byId('sqlContainer').style.height);
		sql.resize();
	}
	//listen for resize changes
	aspect.after(registry.byId('sqlContainer'), 'resize', function() {
		resizeAce();		
	});
	//Set initially
	resizeAce();		
	
	// Function to handle execution of sql code
	var execute = function(event) {
        var query = {};
        query.sql = sql.getSession().getValue();
		if(!query.sql) 
			return;
		query.database = registry.byId('database-header').value;
		query.type = 'init';
		query.total = 0;
		dom.byId('grid').innerHTML = '';
		showResults(false);
		var gridResized = false;
		setStatus('Executing query...', 'processing', '', '');

		var push = [].push; // used in _request
		
		// Pass sql query to the server
		xhr("api/query", {            
            handleAs: "json",
            data: query,                    
            method: "post"
        }).then(function(initData){
            // Handle the returned data. Initially will just be the column definition (no grid data)
            //console.debug('Columns Returned: ', initData);
			var start = Date.now();
            if(initData.error) {
				errorHandler(initData.error);
            } else {		
				if(initData.columns.length == 0) {
					// The query may have been an insert/update or other query not returning any data
					setResults('Query executed successfully.', 'info');
					setStatus('Query executed successfully.', 'info', Date.now() - start, '');
					setDatabase(initData);
				} else {
					query.type = "data";
					dom.byId('grid').innerHTML = '';
					// Now we already have the initData column definitions, we set up the store with the query to return just the grid
					// data, letting the grid do its natural paging etc. TODO: consolodate these 2 calls (one for column definition, 
					// second for data into one call)
					var store = new declare([ Request, Trackable, Cache ])(lang.mixin({
						//target: "api/query?" + ioQuery.objectToQuery(query),
						target: "api/query",

						_request: function (kwArgs) {
							//console.debug('in request',kwArgs);
							kwArgs = kwArgs || {};

							// perform the actual query
							var headers = lang.delegate(this.headers, { Accept: this.accepts });

							if ('headers' in kwArgs) {
								lang.mixin(headers, kwArgs.headers);
							}

							var queryParams = this._renderQueryParams(),
								requestUrl = this.target;

							if ('queryParams' in kwArgs) {
								push.apply(queryParams, kwArgs.queryParams);
							}

							if (queryParams.length > 0) {
								requestUrl += (this._targetContainsQueryString ? '&' : '?') + queryParams.join('&');
							}
							
							var response = request(requestUrl, {
								method: 'POST',
								data: query,
								headers: headers
							});
						   
							var collection = this;
							
							var parsedResponse = response.then(function (response) {
								return collection.parse(response);
								/*
								var zcol = collection.parse(response);
								//grid._setColumns(zcol.columns);
								return zcol;
								*/
							});
							return {
								data: parsedResponse.then(function (data) {
									// support items in the results
									var results = data.items || data;
									resizeGridColumns(initData.columns, results);
									for (var i = 0, l = results.length; i < l; i++) {
										results[i] = collection._restore(results[i], true);
									}
									return results;
								}),
								total: parsedResponse.then(function (data) {
									// check for a total property
									var total = data.total;

									if (total > -1) {
										// if we have a valid positive number from the data,
										// we can use that
										query.total = total; // keep track of total for this query.
										return total;
									}
									// else use headers
									return response.response.then(function (response) {
										var range = response.getHeader('Content-Range');
										return range && (range = range.match(/\/(.*)/)) && +range[1];
									});
								}),

	//							columns: parsedResponse.then(function (data) {
	//								// check for a total property
	//								columns = data.columns;
	//								//console.debug('columns: ', columns);
	//								//grid._setColumns(columns);
	//								console.debug('in promise return from _request. grid is: ', grid);
	//								//grid.columns = columns;
	//								grid._setColumns(columns);
	//								console.debug('in promise return from _request. grid is: ', grid);
	//								//zColumns = columns;
	//								return columns;
	//							}),		

								response: response.response
							};
						}

					}));

					var grid = new (declare([OnDemandGrid, ColumnResizer]))({
						collection: store,
						//columns: zcolumns
						columns: initData.columns
					}, 'grid');
					
					grid.on('dgrid-refresh-complete', function(event) {
						showResults(true);
						setStatus('Query executed successfully.', 'info', Date.now() - start, query.total);
					});

					grid.startup();

					var resizeGridColumns = function(columns, data) {
						if(zoneConfig.column_autosize && !gridResized) {
							var cellFont = '13px tahoma,​verdana,​sans-serif'; //TODO: Determine this more elegantly.
							var headerCellFont = '13px tahoma,​verdana,​sans-serif'; //TODO: Determine this more elegantly.
							var paddingWidth = 10;
							var headerWidths = {};
							for(var idx in data) {
								for(var col in data[idx]) {
									var thisValWidth = data[idx][col] && data[idx][col].length ? getTextWidth(data[idx][col], cellFont) + paddingWidth : 0;
									var largestWidthSoFar = columns[col].width ? columns[col].width : 0;
									if(idx == 0)  // set on first row scan only
										headerWidths[col] = getTextWidth(col, headerCellFont) + paddingWidth;
									// set the highest width of the three so far:
									columns[col].width = Math.max.apply(Math, [thisValWidth, largestWidthSoFar, headerWidths[col]]);
								}
							}
							for(var col in columns) {
								grid.columns[col].width = columns[col].width;
							}
							gridResized = true;
							grid.configStructure();
						}
					};				
				}
			}
		});
	};
	
	// If the execute sql key shortcut is used by Ace Editor, we need to handle
	// it separately, else it won't bubble it up.
	var HashHandler = ace.define.modules['ace/keyboard/hash_handler'].HashHandler;
	var keyboardHandler = new HashHandler();
	keyboardHandler.addCommand({
		name: 'executeShortcut',
		bindKey: 'Ctrl+E', //Ctrl+E|Ctrl+Enter', //{win: 'Ctrl+E', mac: 'Ctrl+E'}
		exec: function() {
			//console.debug('ACE Keydown event', this);
			execute();
			return false;
		}
	});
	sql.keyBinding.addKeyboardHandler(keyboardHandler);
	
	// execute on keypress
	on(window, "keydown", function(event) {
		//console.debug('ctrlKey:' , event.ctrlKey, 'metaKey:', event.metaKey, 'key:', event.key, 'keyCode:', event.keyCode, ' ||| EVENT ', event);
		if(event.ctrlKey == true && event.key == 'e') { //if(event.ctrlKey == true && (event.key == 'e' || event.key == 'Enter')) {
			event.preventDefault(); 
			execute();
		}
	});

	var getTextWidth = function(text, font) {
		// re-use canvas object for better performance
		var canvas = getTextWidth.canvas || (getTextWidth.canvas = document.createElement("canvas"));
		var context = canvas.getContext("2d");
		context.font = font;
		var metrics = context.measureText(text);
		//return metrics.width;
		return Math.floor(metrics.width) + 1;
	};

	var errorHandler = function(error) {
		console.debug('Error: ', error);
		setStatus('Query completed with errors', 'error', '', '');
		setResults(error, 'error');
	};
	
	var setStatus = function(message, type, time, rows) {
		dom.byId('statusMessage').innerHTML = message;
		dom.byId('statusMessage').className = 'statusMessage' + type.charAt(0).toUpperCase() + type.slice(1);
		if(time > 1000) {
			var min = (time/1000/60) << 0;
			var sec = Math.floor(time/1000);
			min = ('0' + min).slice(-2);
			sec = ('0' + sec).slice(-2);
			time = min + ':' + sec;
		} else if(time) {
			time = time + ' ms';
		}
		dom.byId('statusTime').innerHTML = time;
		dom.byId('statusRows').innerHTML = rows ? rows + ' rows' : '';
	};
	
	var setResults = function(message, type) {
		var className = 'results' + type.charAt(0).toUpperCase() + type.slice(1); //dom.byId('statusMessage').className
		message = '<p class="' + className + '">' + message + '</p>';
		showResults(true);
		dom.byId('grid').innerHTML = message;
	};
	
	var setDatabase = function(initData) {
		if(initData.database) {
			var options = registry.byId("database-header").getOptions();
			for(var i=0; i<options.length; i++) {
				if(options[i].value.toLowerCase() == initData.database.toLowerCase()) {
					registry.byId('database-header').attr('value', options[i].value);
				}
			}
	    }
	};	
	
	var pad = function(n, width, z) {
		z = z || '0';
		n = n + '';
		return n.length >= width ? n : new Array(width - n.length + 1).join(z) + n;
	};

	var showResults = function(show) {
		if(show) {
			if(domClass.contains(dom.byId('grid'), 'resultsGridHidden'))
				domClass.remove(dom.byId('grid'), 'resultsGridHidden');
		} else {
			if(!domClass.contains(dom.byId('grid'), 'resultsGridHidden'))
				domClass.add(dom.byId('grid'), 'resultsGridHidden');
		}
		
	};

	var keydownHandler = function(event) {
		if(event.ctrlKey && event.keyCode == 69) {  // ctrl + e
		  event.preventDefault(); 
		  execute();
		}
	};
	
	// Also execute on press of 'Run' button.
	on(registry.byId('execute'), "click", execute);
	
	on(registry.byId('logout'), "click", function(event) {
		window.location.href = './logout/';
	});
	
	// create store
    var myStore = new JsonRest({
        target: "api/",

        getChildren: function(object){
            // object may just be stub object, so get the full object first and then return it's
            // list of children
			return this.get(object.id).then(function(object){
                return object.children ? object.children : object[0].children;
            });
        }
    });

    // create model to interface Tree to store
    var model = new ObjectStoreModel({
        store: myStore,

        mayHaveChildren: function(object){
            return "children" in object;
        }


    });
    // Custom TreeNode class (based on dijit.TreeNode) that allows rich text labels
    var MyTreeNode = declare(Tree._TreeNode, {
        _setLabelAttr: {node: "labelNode", type: "innerHTML"}
    });

    var tree = new Tree({
		error: false,
        model: model,
        _createTreeNode: function(args){
			if(args.item.error) {
				errorHandler(args.item.error);
				this.error = true;
				args.label = 'Disconnected';
			}
		
			return new MyTreeNode(args);
        },
        persist: false,

        getIconClass: function(/*dojo.storeItem*/ item, /*BooLean*/ opened){
			if(this.error) {
				return 'dijitIconConnector';
			}

            // Calculate the depth the node is in the tree, to assist in selecting the icon
            var treeDepth = (item.id.match(/\//g) || []).length;

            if(item.id == 'server') 
                return 'dijitIconConnector';
            else if(treeDepth == 0) 
                return 'dijitIconDatabase';
            else if(treeDepth == 1) 
                return 'dijitIconTable';
            else 
                return 'dijitLeaf';
            
        }
    }, "tree"); 
    tree.startup();

});
