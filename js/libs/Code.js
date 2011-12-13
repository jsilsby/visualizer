function Code(src) {

	this.source = src;
	
	this.getParent = function() {
		var ret = "";
		try {
			var exp = "prototype = new ";
			var index = this.source.search(exp);
		
			if (index > 0) {
				ret = this.source.substring(index + 16, this.source.length - 1);
		
			}
		} catch (e) {
			alert(e);
		}
		
		return ret;
	}
	
	this.getComposition = function() {
		var ret = [];
		
		try {
			var exp = "= new ";
			var i = 0;
			while((index = this.source.indexOf(exp, i)) > -1) {
				var tmp = this.source.substring(index + 6);
				var cls = tmp.substring(0, tmp.indexOf("("));
				
				if (ret.join().indexOf(cls) < 0) {
					ret.push(cls);
				}
				i = index + 6;
			}
			
		} catch (e) {
			alert(e);
		}
		
		return ret;
	}
}