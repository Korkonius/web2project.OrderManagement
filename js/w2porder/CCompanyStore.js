define(["dojo/store/util/QueryResults", "dojo/_base/declare", "dojo/_base/xhr", "dojo/_base/lang", "dojo/store/util/SimpleQueryEngine"],
    function(QueryResults, declare, xhr, lang, SimpleQueryEngine){
        return declare(null, {
            data: [],
            index: {},
            idProperty: "id",
            queryEngine: SimpleQueryEngine,
            constructor: function() {

                // Populate the store using the given URL
                var xhrParam = {
                    "url": "?m=ordermgmt&a=cedit&op=getCompanies&suppressHeaders=true",
                    handleAs: "json",
                    sync: false,
                    error: function(crap) {
                        alert(crap.message);
                    }
                }
                dojo.xhrGet(xhrParam).then(lang.hitch(this, function(data){
                    this.setData(data);
                }));
            },
            query: function(query, options){
                return QueryResults((this.queryEngine(query, options))(this.data));
            },
            setData: function(data) {
                this.data = data
                this.index = {};
                for(var i = 0, l = data.length; i < l; i++){
                    var object = data[i];
                    this.index[object[this.idProperty]] = object;
                }
            },
            get: function(id) {
                return this.index[id];
            },
            getIdentity: function(object) {
                return object[this.idProperty];
            }
        });
    });