$(function(){
	var graph = new NodeGraph();
	var projectList = {};
	var resourceList = [];
	
	// consider moving to NodeGraph
	$("#canvas").mouseup(function(e){
		if (openWin.css("display") == "none"){
			var children = $(e.target).children();
			if (children.length > 0){
				var type = children[0].tagName;
				if (type == "desc" || type == "SPAN"){
					graph.addNodeAtMouse();
				}
			}
		}
	});
  
	// ui code
	var openWin = $("#openWin");
	openWin.hide();
 
	$(".btn").mouseenter(function(){
		$(this).animate({"backgroundColor" : "white"}, 200);
	}).mouseleave(function(){
		$(this).animate({"backgroundColor" : "#efefef"});
	});
  
	$("#clear").click(function(){
		graph.clearAll();
	});
	
	$("#help").click(function(){
		window.open("http://www.zreference.com/znode", "_blank");
	});
  
	// get project name
	var msg = "Enter your project name";
	var projectName = $("#projectname").val(msg);
	projectName.focus(function(){
		if ($(this).val() == msg){
			$(this).val("");
		}
	}).blur(function(){
		if ($(this).val() == ""){
			$(this).val(nameMessage);
		}
	});
  
	// open Project event
	$("#openProject").click(function(){
		var name = projectName.val();
		var files = $("#hidden_area");
		files.load("json/project.php?directory=" + name);
		
		alert("Project Loaded");
	});
	
	// set project list array
	function setProjectList() {
		try {	
			var elements = document.getElementsByName("file_name");
			for (var e = 0 ; e < elements.length; e++) {
				var p = {};
				var src = elements[e].childNodes[0].innerHTML;
				var code = new Code(src);
				var parent = code.getParent();
					
				if (parent != "") {
					parent += ".js";
				}
					
				p["idx"] = e;
				p["name"] = elements[e].title
				p["src"] = src;
				p["parent"] = parent;
				projectList[elements[e].title] = p;
			}
			computeRelationship();
		} catch (e) {
			alert(e);
		}
	}
	
	//
	function setResourceList() {
		try {
			var elements = document.getElementsByName("resource_name");
			for (var e = 0 ; e < elements.length; e++) {
				resourceList.push(elements[e].innerHTML);
			}
		} catch (e) {
			alert(e);
		}
	}
	
	function computeRelationship() {
		try {
			for (var i in projectList) {
				var element = projectList[i];
				var parent = element.parent;
				var p_e = projectList[parent];
				if (p_e) {
					element["p_idx"] = p_e.idx;
					p_e["c_idx"] = element.idx;
				} else {
					element["parent"] = "";
				}
			}
		} catch (e) {
			alert(e);
		}
	}
	
	// hierarchy button click
	$("#views").click(function() {
		var links =  $("#view_link");
		links.html("<div>loading...<\/div>");
		openWin.fadeIn();
		links.html("<div class='all_class'>All Classes</div><div class='hierarchy'>Hierarchy</div><div class='composition'>Composition</div><div class='globals'>Globals</div><div class='resource'>Resource</div>");
	});
	
    // 
	function createRepository() {
		if (isEmpty(projectList)) {
				setProjectList();
		}
		
		var repo = {};
		
		for (var i in projectList) {
			var element = projectList[i];
			var p = {};
			p["name"] = element.name;
			p["src"] = element.src;
			repo[element.idx] = p;
		}
		return repo;
	}

	//
	$(".all_class").live('click', function() {
		try {
			var obj = JSON.parse(toAllJSON());
			graph.setCodeRepository(createRepository());
			graph.fromJSON(obj);
		} catch (e) {
			alert(e);
		}
	}).live('mouseover', function(){
		$(this).css({"background-color": "#ededed"});
	}).live("mouseout", function(){
		$(this).css({"background-color": "white"});
	});
	
		// create hiearchy JSON layout
	function toAllJSON() {		
		var json = "";
		
		try {
			var x_pos = 300;
			var y_pos = 100;
			var hasConnections = false;
			var con = '], "connections" : [';
			json = '{"nodes" : [';
			
			if (isEmpty(projectList)) {
				setProjectList();
			}
			
			for (var i in projectList) {
				var n = projectList[i];
				json += '{"id" : ' + n.idx + ', ';
				json += '"x" : ' + x_pos + ', ';
				json += '"y" : ' + y_pos + ', ';
				json += '"width" : ' + 100 + ', ';
				json += '"height" : ' + 50 + ', ';
				json += '"txt" : "' + n.name + '"},';
				
				y_pos += 150;
				if (y_pos > 400) {
					x_pos += 150;
					y_pos = 100;
				}
			}
			
			json = json.substr(0, json.length - 1);
		
			json += con;
			if (hasConnections){
				json = json.substr(0, json.length - 1);
			}
			json += ']}';
		} catch (e) {
			alert(e);
		}
		return json;
	}
	
	//
	$(".globals").live('click', function() {
		alert('not implemented');
	}).live('mouseover', function(){
		$(this).css({"background-color": "#ededed"});
	}).live("mouseout", function(){
		$(this).css({"background-color": "white"});
	});

	//
	$(".resource").live('click', function() {
		try {
			var obj = JSON.parse(toResourceJSON());
			graph.setCodeRepository({});
			graph.fromJSON(obj);
		} catch (e) {
			alert(e);
		}
	}).live('mouseover', function(){
		$(this).css({"background-color": "#ededed"});
	}).live("mouseout", function(){
		$(this).css({"background-color": "white"});
	});
	
	// create hiearchy JSON layout
	function toResourceJSON() {		
		var json = "";
		
		try {
			var x_pos = 300;
			var y_pos = 100;
			var hasConnections = false;
			var con = '], "connections" : [';
			json = '{"nodes" : [';
			
			if (resourceList.length < 1) {
				setResourceList();
				
			}
			
			for (i = 0; i < resourceList.length; i++) {
				var n = resourceList[i];
				json += '{"id" : ' + i + ', ';
				json += '"x" : ' + x_pos + ', ';
				json += '"y" : ' + y_pos + ', ';
				json += '"width" : ' + 100 + ', ';
				json += '"height" : ' + 50 + ', ';
				json += '"txt" : "' + n + '"},';
					
				y_pos += 150;
				if (y_pos > 400) {
					x_pos += 150;
					y_pos = 100;
				}
			}
			
			json = json.substr(0, json.length - 1);
		
			json += con;
			if (hasConnections){
				json = json.substr(0, json.length - 1);
			}
			json += ']}';
		} catch (e) {
			alert(e);
		}
		return json;
	}
	
	//
	$(".composition").live('click', function() {
		alert('not implemented');
	}).live('mouseover', function(){
		$(this).css({"background-color": "#ededed"});
	}).live("mouseout", function(){
		$(this).css({"background-color": "white"});
	});
	
	//
	function toCompositionJSON() {
		
	}
	
	//
	$(".hierarchy").live('click', function() {
		try {
			var obj = JSON.parse(toHierarchyJSON());
			graph.setCodeRepository(createRepository());
			graph.fromJSON(obj);
		} catch (e) {
			alert(e);
		}
	}).live('mouseover', function(){
		$(this).css({"background-color": "#ededed"});
	}).live("mouseout", function(){
		$(this).css({"background-color": "white"});
	});
	
	
	// create hiearchy JSON layout
	function toHierarchyJSON() {		
		var json = "";
		
		try {
			var x_pos = 300;
			var y_pos = 100;
			var hasConnections = false;
			var con = '], "connections" : [';
			json = '{"nodes" : [';
			
			if (isEmpty(projectList)) {
				setProjectList();
			}
			
			for (var i in projectList) {
				var n = projectList[i];
				if (n.p_idx >= 0|| n.c_idx >= 0) {
					json += '{"id" : ' + n.idx + ', ';
					json += '"x" : ' + x_pos + ', ';
					json += '"y" : ' + y_pos + ', ';
					json += '"width" : ' + 100 + ', ';
					json += '"height" : ' + 50 + ', ';
					json += '"txt" : "' + n.name + '"},';
				
					if (n.p_idx >= 0) {
						con += '{"nodeA" : ' + n.idx + ', ';
						con += '"nodeB" : ' + n.p_idx + ', ';
						con += '"conA" : "top", ';
						con += '"conB" : "bottom"},';
						hasConnections = true;
					}
					
					y_pos += 150;					
					if (y_pos > 400) {
						x_pos += 150;
						y_pos = 100;
					}
				}
			}
			
			json = json.substr(0, json.length - 1);
		
			json += con;
			if (hasConnections){
				json = json.substr(0, json.length - 1);
			}
			json += ']}';
		} catch (e) {
			alert(e);
		}
		return json;
	}
	
	$("#canvas").mousedown(function(){
		openWin.fadeOut();
	});
  
	function isEmpty(o){
		for(var i in o){
			if(o.hasOwnProperty(i)){
				return false;
			}
		}
		return true;
	}
});