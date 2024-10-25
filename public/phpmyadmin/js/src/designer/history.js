/**
 * @fileoverview    function used in this file builds history tab and generates query.
 *
 * @requires    jQuery
 * @requires    move.js
 */

/* global contr */ // js/designer/init.js
/* global fromArray:writable */ // js/designer/move.js
/* global themeImagePath */ // templates/javascript/variables.twig

var DesignerHistory = {};

var historyArray = []; // Global array to store history objects
var selectField = [];  // Global array to store information for columns which are used in select clause
var gIndex;
var vqbEditor = null;

/**
 * To display details of objects(where,rename,Having,aggregate,groupby,orderby,having)
 *
 * @param {number} index index of historyArray where change is to be made
 * @return {string}
 */
DesignerHistory.detail = function (index) {
    var type = historyArray[index].getType();
    var str;
    if (type === 'Where') {
        str = 'Where ' + historyArray[index].getColumnName() + historyArray[index].getObj().getRelationOperator() + historyArray[index].getObj().getQuery();
    } else if (type === 'Rename') {
        str = 'Rename ' + historyArray[index].getColumnName() + ' To ' + historyArray[index].getObj().getRenameTo();
    } else if (type === 'Aggregate') {
        str = 'Select ' + historyArray[index].getObj().getOperator() + '( ' + historyArray[index].getColumnName() + ' )';
    } else if (type === 'GroupBy') {
        str = 'GroupBy ' + historyArray[index].getColumnName();
    } else if (type === 'OrderBy') {
        str = 'OrderBy ' + historyArray[index].getColumnName() + ' ' + historyArray[index].getObj().getOrder();
    } else if (type === 'Having') {
        str = 'Having ';
        if (historyArray[index].getObj().getOperator() !== 'None') {
            str += historyArray[index].getObj().getOperator() + '( ' + historyArray[index].getColumnName() + ' )';
            str += historyArray[index].getObj().getRelationOperator() + historyArray[index].getObj().getQuery();
        } else {
            str = 'Having ' + historyArray[index].getColumnName() + historyArray[index].getObj().getRelationOperator() + historyArray[index].getObj().getQuery();
        }
    }
    return str;
};

/**
 * Sorts historyArray[] first,using table name as the key and then generates the HTML code for history tab,
 * clubbing all objects of same tables together
 * This function is called whenever changes are made in historyArray[]
 *
 *
 * @param {number} init starting index of unsorted array
 * @param {number} finit last index of unsorted array
 * @return {string}
 */
DesignerHistory.display = function (init, finit) {
    var str;
    var i;
    var j;
    var k;
    var sto;
    var temp;
    // this part sorts the history array based on table name,this is needed for clubbing all object of same name together.
    for (i = init; i < finit; i++) {
        sto = historyArray[i];
        temp = historyArray[i].getTab();// + '.' + historyArray[i].getObjNo(); for Self JOINS
        for (j = 0; j < i; j++) {
            if (temp > (historyArray[j].getTab())) {// + '.' + historyArray[j].getObjNo())) { //for Self JOINS
                for (k = i; k > j; k--) {
                    historyArray[k] = historyArray[k - 1];
                }
                historyArray[j] = sto;
                break;
            }
        }
    }
    // this part generates HTML code for history tab.adds delete,edit,and/or and detail features with objects.
    str = ''; // string to store Html code for history tab
    var historyArrayLength = historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        temp = historyArray[i].getTab(); // + '.' + historyArray[i].getObjNo(); for Self JOIN
        str += '<h3 class="tiger"><a href="#">' + temp + '</a></h3>';
        str += '<div class="toggle_container">\n';
        while ((historyArray[i].getTab()) === temp) { // + '.' + historyArray[i].getObjNo()) === temp) {
            str += '<div class="block"> <table class="table table-sm w-auto mb-0">';
            str += '<thead><tr><td>';
            if (historyArray[i].getAndOr()) {
                str += '<img src="' + themeImagePath + 'designer/or_icon.png" onclick="DesignerHistory.andOr(' + i + ')" title="OR"></td>';
            } else {
                str += '<img src="' + themeImagePath + 'designer/and_icon.png" onclick="DesignerHistory.andOr(' + i + ')" title="AND"></td>';
            }
            str += '<td style="padding-left: 5px;" class="text-end">' + Functions.getImage('b_sbrowse', Messages.strColumnName) + '</td>' +
                '<td width="175" style="padding-left: 5px">' + $('<div/>').text(historyArray[i].getColumnName()).html() + '<td>';
            if (historyArray[i].getType() === 'GroupBy' || historyArray[i].getType() === 'OrderBy') {
                var detailDescGroupBy = $('<div/>').text(DesignerHistory.detail(i)).html();
                str += '<td class="text-center">' + Functions.getImage('s_info', DesignerHistory.detail(i)) + '</td>' +
                    '<td title="' + detailDescGroupBy + '">' + historyArray[i].getType() + '</td>' +
                    '<td onclick=DesignerHistory.historyDelete(' + i + ')>' + Functions.getImage('b_drop', Messages.strDelete) + '</td>';
            } else {
                var detailDesc = $('<div/>').text(DesignerHistory.detail(i)).html();
                str += '<td class="text-center">' + Functions.getImage('s_info', DesignerHistory.detail(i)) + '</td>' +
                    '<td title="' + detailDesc + '">' + historyArray[i].getType() + '</td>' +
                    '<td onclick=DesignerHistory.historyEdit(' + i + ')>' + Functions.getImage('b_edit', Messages.strEdit) + '</td>' +
                    '<td onclick=DesignerHistory.historyDelete(' + i + ')>' + Functions.getImage('b_drop', Messages.strDelete) + '</td>';
            }
            str += '</tr></thead>';
            i++;
            if (i >= historyArrayLength) {
                break;
            }
            str += '</table></div>';
        }
        i--;
        str += '</div>';
    }
    return str;
};

/**
 * To change And/Or relation in history tab
 *
 *
 * @param {number} index index of historyArray where change is to be made
 * @return {void}
 */
DesignerHistory.andOr = function (index) {
    if (historyArray[index].getAndOr()) {
        historyArray[index].setAndOr(0);
    } else {
        historyArray[index].setAndOr(1);
    }
    var existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = DesignerHistory.display(0, 0);
    $('#ab').accordion('refresh');
};

/**
 * Deletes entry in historyArray
 *
 * @param {number} index of historyArray[] which is to be deleted
 * @return {void}
 */
DesignerHistory.historyDelete = function (index) {
    var fromArrayLength = fromArray.length;
    for (var k = 0; k < fromArrayLength; k++) {
        if (fromArray[k] === historyArray[index].getTab()) {
            fromArray.splice(k, 1);
            break;
        }
    }
    historyArray.splice(index, 1);
    var existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = DesignerHistory.display(0, 0);
    $('#ab').accordion('refresh');
};

/**
 * @param {string} elementId
 * @return {void}
 */
DesignerHistory.changeStyle = function (elementId) {
    var element = document.getElementById(elementId);
    element.style.left =  '530px';
    element.style.top  = '130px';
    element.style.position  = 'absolute';
    element.style.zIndex = '103';
    element.style.visibility = 'visible';
    element.style.display = 'block';
};

/**
 * To show where,rename,aggregate,having forms to edit a object
 *
 * @param {number} index index of historyArray where change is to be made
 * @return {void}
 */
DesignerHistory.historyEdit = function (index) {
    gIndex = index;
    var type = historyArray[index].getType();
    if (type === 'Where') {
        document.getElementById('eQuery').value = historyArray[index].getObj().getQuery();
        document.getElementById('erel_opt').value = historyArray[index].getObj().getRelationOperator();
        DesignerHistory.changeStyle('query_where');
    } else if (type === 'Having') {
        document.getElementById('hQuery').value = historyArray[index].getObj().getQuery();
        document.getElementById('hrel_opt').value = historyArray[index].getObj().getRelationOperator();
        document.getElementById('hoperator').value = historyArray[index].getObj().getOperator();
        DesignerHistory.changeStyle('query_having');
    } else if (type === 'Rename') {
        document.getElementById('e_rename').value = historyArray[index].getObj().getRenameTo();
        DesignerHistory.changeStyle('query_rename_to');
    } else if (type === 'Aggregate') {
        document.getElementById('e_operator').value = historyArray[index].getObj().getOperator();
        DesignerHistory.changeStyle('query_Aggregate');
    }
};

/**
 * Make changes in historyArray when Edit button is clicked
 * checks for the type of object and then sets the new value
 *
 * @param {string} type of historyArray where change is to be made
 * @return {void}
 */
DesignerHistory.edit = function (type) {
    if (type === 'Rename') {
        if (document.getElementById('e_rename').value !== '') {
            historyArray[gIndex].getObj().setRenameTo(document.getElementById('e_rename').value);
            document.getElementById('e_rename').value = '';
        }
        document.getElementById('query_rename_to').style.visibility = 'hidden';
    } else if (type === 'Aggregate') {
        if (document.getElementById('e_operator').value !== '---') {
            historyArray[gIndex].getObj().setOperator(document.getElementById('e_operator').value);
            document.getElementById('e_operator').value = '---';
        }
        document.getElementById('query_Aggregate').style.visibility = 'hidden';
    } else if (type === 'Where') {
        if (document.getElementById('erel_opt').value !== '--' && document.getElementById('eQuery').value !== '') {
            historyArray[gIndex].getObj().setQuery(document.getElementById('eQuery').value);
            historyArray[gIndex].getObj().setRelationOperator(document.getElementById('erel_opt').value);
        }
        document.getElementById('query_where').style.visibility = 'hidden';
    } else if (type === 'Having') {
        if (document.getElementById('hrel_opt').value !== '--' && document.getElementById('hQuery').value !== '') {
            historyArray[gIndex].getObj().setQuery(document.getElementById('hQuery').value);
            historyArray[gIndex].getObj().setRelationOperator(document.getElementById('hrel_opt').value);
            historyArray[gIndex].getObj().setOperator(document.getElementById('hoperator').value);
        }
        document.getElementById('query_having').style.visibility = 'hidden';
    }
    var existingDiv = document.getElementById('ab');
    existingDiv.innerHTML = DesignerHistory.display(0, 0);
    $('#ab').accordion('refresh');
};

/**
 * history object closure
 *
 * @param nColumnName  name of the column on which conditions are put
 * @param nObj          object details(where,rename,orderby,groupby,aggregate)
 * @param nTab          table name of the column on which conditions are applied
 * @param nObjNo       object no used for inner join
 * @param nType         type of object
 *
 */
DesignerHistory.HistoryObj = function (nColumnName, nObj, nTab, nObjNo, nType) {
    var andOr;
    var obj;
    var tab;
    var columnName;
    var objNo;
    var type;
    this.setColumnName = function (nColumnName) {
        columnName = nColumnName;
    };
    this.getColumnName = function () {
        return columnName;
    };
    this.setAndOr = function (nAndOr) {
        andOr = nAndOr;
    };
    this.getAndOr = function () {
        return andOr;
    };
    this.getRelation = function () {
        return andOr;
    };
    this.setObj = function (nObj) {
        obj = nObj;
    };
    this.getObj = function () {
        return obj;
    };
    this.setTab = function (nTab) {
        tab = nTab;
    };
    this.getTab = function () {
        return tab;
    };
    this.setObjNo = function (nObjNo) {
        objNo = nObjNo;
    };
    this.getObjNo = function () {
        return objNo;
    };
    this.setType = function (nType) {
        type = nType;
    };
    this.getType = function () {
        return type;
    };
    this.setObjNo(nObjNo);
    this.setTab(nTab);
    this.setAndOr(0);
    this.setObj(nObj);
    this.setColumnName(nColumnName);
    this.setType(nType);
};

/**
 * where object closure, makes an object with all information of where
 *
 * @param nRelationOperator type of relation operator to be applied
 * @param nQuery             stores value of value/sub-query
 *
 */

DesignerHistory.Where = function (nRelationOperator, nQuery) {
    var relationOperator;
    var query;
    this.setRelationOperator = function (nRelationOperator) {
        relationOperator = nRelationOperator;
    };
    this.setQuery = function (nQuery) {
        query = nQuery;
    };
    this.getQuery = function () {
        return query;
    };
    this.getRelationOperator = function () {
        return relationOperator;
    };
    this.setQuery(nQuery);
    this.setRelationOperator(nRelationOperator);
};

/**
 * Orderby object closure
 *
 * @param nOrder order, ASC or DESC
 */
DesignerHistory.OrderBy = function (nOrder) {
    var order;
    this.setOrder = function (nOrder) {
        order = nOrder;
    };
    this.getOrder = function () {
        return order;
    };
    this.setOrder(nOrder);
};

/**
 * Having object closure, makes an object with all information of where
 *
 * @param nRelationOperator type of relation operator to be applied
 * @param nQuery             stores value of value/sub-query
 * @param nOperator          operator
 */
DesignerHistory.Having = function (nRelationOperator, nQuery, nOperator) {
    var relationOperator;
    var query;
    var operator;
    this.setOperator = function (nOperator) {
        operator = nOperator;
    };
    this.setRelationOperator = function (nRelationOperator) {
        relationOperator = nRelationOperator;
    };
    this.setQuery = function (nQuery) {
        query = nQuery;
    };
    this.getQuery = function () {
        return query;
    };
    this.getRelationOperator = function () {
        return relationOperator;
    };
    this.getOperator = function () {
        return operator;
    };
    this.setQuery(nQuery);
    this.setRelationOperator(nRelationOperator);
    this.setOperator(nOperator);
};

/**
 * rename object closure,makes an object with all information of rename
 *
 * @param nRenameTo new name information
 *
 */
DesignerHistory.Rename = function (nRenameTo) {
    var renameTo;
    this.setRenameTo = function (nRenameTo) {
        renameTo = nRenameTo;
    };
    this.getRenameTo = function () {
        return renameTo;
    };
    this.setRenameTo(nRenameTo);
};

/**
 * aggregate object closure
 *
 * @param nOperator aggregate operator
 *
 */
DesignerHistory.Aggregate = function (nOperator) {
    var operator;
    this.setOperator = function (nOperator) {
        operator = nOperator;
    };
    this.getOperator = function () {
        return operator;
    };
    this.setOperator(nOperator);
};

/**
 * This function returns unique element from an array
 *
 * @param arrayName array from which duplicate elem are to be removed.
 * @return unique array
 */

DesignerHistory.unique = function (arrayName) {
    var newArray = [];
    uniquetop:
    for (var i = 0; i < arrayName.length; i++) {
        var newArrayLength = newArray.length;
        for (var j = 0; j < newArrayLength; j++) {
            if (newArray[j] === arrayName[i]) {
                continue uniquetop;
            }
        }
        newArray[newArrayLength] = arrayName[i];
    }
    return newArray;
};

/**
 * This function takes in array and a value as input and returns 1 if values is present in array
 * else returns -1
 *
 * @param arrayName array
 * @param value  value which is to be searched in the array
 */

DesignerHistory.found = function (arrayName, value) {
    var arrayNameLength = arrayName.length;
    for (var i = 0; i < arrayNameLength; i++) {
        if (arrayName[i] === value) {
            return 1;
        }
    }
    return -1;
};

/**
 * This function concatenates two array
 *
 * @param {object} add array elements of which are pushed in
 * @param {obj[]} arr array in which elements are added
 *
 * @return {obj[]}
 */
DesignerHistory.addArray = function (add, arr) {
    var addLength = add.length;
    for (var i = 0; i < addLength; i++) {
        arr.push(add[i]);
    }
    return arr;
};

/**
 * This function removes all elements present in one array from the other.
 *
 * @param {object} rem array from which each element is removed from other array.
 * @param {obj[]} arr array from which elements are removed.
 *
 * @return {obj[]}
 *
 */
DesignerHistory.removeArray = function (rem, arr) {
    var remLength = rem.length;
    for (var i = 0; i < remLength; i++) {
        var arrLength = arr.length;
        for (var j = 0; j < arrLength; j++) {
            if (rem[i] === arr[j]) {
                arr.splice(j, 1);
            }
        }
    }
    return arr;
};

/**
 * This function builds the groupby clause from history object
 * @return {string}
 */
DesignerHistory.queryGroupBy = function () {
    var i;
    var str = '';
    var historyArrayLength = historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        if (historyArray[i].getType() === 'GroupBy') {
            str += '`' + historyArray[i].getColumnName() + '`, ';
        }
    }
    str = str.substr(0, str.length - 2);
    return str;
};

/**
 * This function builds the Having clause from the history object.
 * @return {string}
 */
DesignerHistory.queryHaving = function () {
    var i;
    var and = '(';
    var historyArrayLength = historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        if (historyArray[i].getType() === 'Having') {
            if (historyArray[i].getObj().getOperator() !== 'None') {
                and += historyArray[i].getObj().getOperator() + '(`' + historyArray[i].getColumnName() + '`) ' + historyArray[i].getObj().getRelationOperator();
                and += ' ' + historyArray[i].getObj().getQuery() + ', ';
            } else {
                and += '`' + historyArray[i].getColumnName() + '` ' + historyArray[i].getObj().getRelationOperator() + ' ' + historyArray[i].getObj().getQuery() + ', ';
            }
        }
    }
    if (and === '(') {
        and = '';
    } else {
        and = and.substr(0, and.length - 2) + ')';
    }
    return and;
};


/**
 * This function builds the orderby clause from the history object.
 * @return {string}
 */
DesignerHistory.queryOrderBy = function () {
    var i;
    var str = '';
    var historyArrayLength = historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        if (historyArray[i].getType() === 'OrderBy') {
            str += '`' + historyArray[i].getColumnName() + '` ' +
                historyArray[i].getObj().getOrder() + ', ';
        }
    }
    str = str.substr(0, str.length - 2);
    return str;
};


/**
 * This function builds the Where clause from the history object.
 * @return {string}
 */
DesignerHistory.queryWhere = function () {
    var i;
    var and = '(';
    var or = '(';
    var historyArrayLength = historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        if (historyArray[i].getType() === 'Where') {
            if (historyArray[i].getAndOr() === 0) {
                and += '( `' + historyArray[i].getColumnName() + '` ' + historyArray[i].getObj().getRelationOperator() + ' ' + historyArray[i].getObj().getQuery() + ')';
                and += ' AND ';
            } else {
                or += '( `' + historyArray[i].getColumnName() + '` ' + historyArray[i].getObj().getRelationOperator() + ' ' + historyArray[i].getObj().getQuery() + ')';
                or += ' OR ';
            }
        }
    }
    if (or !== '(') {
        or = or.substring(0, (or.length - 4)) + ')';
    } else {
        or = '';
    }
    if (and !== '(') {
        and = and.substring(0, (and.length - 5)) + ')';
    } else {
        and = '';
    }
    if (or !== '') {
        and = and + ' OR ' + or + ' )';
    }
    return and;
};

DesignerHistory.checkAggregate = function (idThis) {
    var i;
    var historyArrayLength = historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        var temp = '`' + historyArray[i].getTab() + '`.`' + historyArray[i].getColumnName() + '`';
        if (temp === idThis && historyArray[i].getType() === 'Aggregate') {
            return historyArray[i].getObj().getOperator() + '(' + idThis + ')';
        }
    }
    return '';
};

DesignerHistory.checkRename = function (idThis) {
    var i;
    var historyArrayLength = historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        var temp = '`' + historyArray[i].getTab() + '`.`' + historyArray[i].getColumnName() + '`';
        if (temp === idThis && historyArray[i].getType() === 'Rename') {
            return ' AS `' + historyArray[i].getObj().getRenameTo() + '`';
        }
    }
    return '';
};

/**
  * This function builds from clause of query
  * makes automatic joins.
  *
  * @return {string}
  */
DesignerHistory.queryFrom = function () {
    var i;
    var tabLeft = [];
    var tabUsed = [];
    var tTabLeft = [];
    var temp;
    var query = '';
    var quer = '';
    var parts = [];
    var tArray = [];
    tArray = fromArray;
    var K = 0;
    var k;
    var key;
    var key2;
    var key3;
    var parts1;

    // the constraints that have been used in the LEFT JOIN
    var constraintsAdded = [];

    var historyArrayLength = historyArray.length;
    for (i = 0; i < historyArrayLength; i++) {
        fromArray.push(historyArray[i].getTab());
    }
    fromArray = DesignerHistory.unique(fromArray);
    tabLeft = fromArray;
    temp = tabLeft.shift();
    quer = '`' + temp + '`';
    tabUsed.push(temp);

    // if master table (key2) matches with tab used get all keys and check if tab_left matches
    // after this check if master table (key2) matches with tab left then check if any foreign matches with master .
    for (i = 0; i < 2; i++) {
        for (K in contr) {
            for (key in contr[K]) {// contr name
                for (key2 in contr[K][key]) {// table name
                    parts = key2.split('.');
                    if (DesignerHistory.found(tabUsed, parts[1]) > 0) {
                        for (key3 in contr[K][key][key2]) {
                            parts1 = contr[K][key][key2][key3][0].split('.');
                            if (DesignerHistory.found(tabLeft, parts1[1]) > 0) {
                                if (DesignerHistory.found(constraintsAdded, key) > 0) {
                                    query += ' AND ' + '`' + parts[1] + '`.`' + key3 + '` = ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` ';
                                } else {
                                    query += '\n' + 'LEFT JOIN ';
                                    query += '`' + parts[1] + '` ON ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` = ';
                                    query += '`' + parts[1] + '`.`' + key3 + '` ';

                                    constraintsAdded.push(key);
                                }
                                tTabLeft.push(parts[1]);
                            }
                        }
                    }
                }
            }
        }
        K = 0;
        tTabLeft = DesignerHistory.unique(tTabLeft);
        tabUsed = DesignerHistory.addArray(tTabLeft, tabUsed);
        tabLeft = DesignerHistory.removeArray(tTabLeft, tabLeft);
        tTabLeft = [];
        for (K in contr) {
            for (key in contr[K]) {
                for (key2 in contr[K][key]) {// table name
                    parts = key2.split('.');
                    if (DesignerHistory.found(tabLeft, parts[1]) > 0) {
                        for (key3 in contr[K][key][key2]) {
                            parts1 = contr[K][key][key2][key3][0].split('.');
                            if (DesignerHistory.found(tabUsed, parts1[1]) > 0) {
                                if (DesignerHistory.found(constraintsAdded, key) > 0) {
                                    query += ' AND ' + '`' + parts[1] + '`.`' + key3 + '` = ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` ';
                                } else {
                                    query += '\n' + 'LEFT JOIN ';
                                    query += '`' + parts[1] + '` ON ';
                                    query += '`' + parts1[1] + '`.`' + contr[K][key][key2][key3][1] + '` = ';
                                    query += '`' + parts[1] + '`.`' + key3 + '` ';

                                    constraintsAdded.push(key);
                                }
                                tTabLeft.push(parts[1]);
                            }
                        }
                    }
                }
            }
        }
        tTabLeft = DesignerHistory.unique(tTabLeft);
        tabUsed = DesignerHistory.addArray(tTabLeft, tabUsed);
        tabLeft = DesignerHistory.removeArray(tTabLeft, tabLeft);
        tTabLeft = [];
    }
    for (k in tabLeft) {
        quer += ' , `' + tabLeft[k] + '`';
    }
    query = quer + query;
    fromArray = tArray;
    return query;
};

/**
 * This function is the main function for query building.
 * uses history object details for this.
 *
 * @uses DesignerHistory.queryWhere()
 * @uses DesignerHistory.queryGroupBy()
 * @uses DesignerHistory.queryHaving()
 * @uses DesignerHistory.queryOrderBy()
 */
DesignerHistory.buildQuery = function () {
    var qSelect = 'SELECT ';
    var temp;
    var selectFieldLength = selectField.length;
    if (selectFieldLength > 0) {
        for (var i = 0; i < selectFieldLength; i++) {
            temp = DesignerHistory.checkAggregate(selectField[i]);
            if (temp !== '') {
                qSelect += temp;
                temp = DesignerHistory.checkRename(selectField[i]);
                qSelect += temp + ', ';
            } else {
                temp = DesignerHistory.checkRename(selectField[i]);
                qSelect += selectField[i] + temp + ', ';
            }
        }
        qSelect = qSelect.substring(0, qSelect.length - 2);
    } else {
        qSelect += '* ';
    }

    qSelect += '\nFROM ' + DesignerHistory.queryFrom();

    var qWhere = DesignerHistory.queryWhere();
    if (qWhere !== '') {
        qSelect += '\nWHERE ' + qWhere;
    }

    var qGroupBy = DesignerHistory.queryGroupBy();
    if (qGroupBy !== '') {
        qSelect += '\nGROUP BY ' + qGroupBy;
    }

    var qHaving = DesignerHistory.queryHaving();
    if (qHaving !== '') {
        qSelect += '\nHAVING ' + qHaving;
    }

    var qOrderBy = DesignerHistory.queryOrderBy();
    if (qOrderBy !== '') {
        qSelect += '\nORDER BY ' + qOrderBy;
    }
    $('#buildQuerySubmitButton').on('click', function () {
        if (vqbEditor) {
            var $elm = $('#buildQueryModal').find('textarea');
            vqbEditor.save();
            $elm.val(vqbEditor.getValue());
        }
        $('#vqb_form').trigger('submit');
    });

    $('#buildQueryModal').modal('show');
    $('#buildQueryModalLabel').first().text('SELECT');
    $('#buildQueryModal').on('shown.bs.modal', function () {
        // Attach syntax highlighted editor to query dialog
        /**
         * @var $elm jQuery object containing the reference
         *           to the query textarea.
         */
        var $elm = $('#buildQueryModal').find('textarea');
        if (! vqbEditor) {
            vqbEditor = Functions.getSqlEditor($elm);
        }
        if (vqbEditor) {
            vqbEditor.setValue(qSelect);
            vqbEditor.focus();
        } else {
            $elm.val(qSelect);
            $elm.trigger('focus');
        }
    });
};

AJAX.registerTeardown('designer/history.js', function () {
    vqbEditor = null;
    historyArray = [];
    selectField = [];
    $('#ok_edit_rename').off('click');
    $('#ok_edit_having').off('click');
    $('#ok_edit_Aggr').off('click');
    $('#ok_edit_where').off('click');
});

AJAX.registerOnload('designer/history.js', function () {
    $('#ok_edit_rename').on('click', function () {
        DesignerHistory.edit('Rename');
    });
    $('#ok_edit_having').on('click', function () {
        DesignerHistory.edit('Having');
    });
    $('#ok_edit_Aggr').on('click', function () {
        DesignerHistory.edit('Aggregate');
    });
    $('#ok_edit_where').on('click', function () {
        DesignerHistory.edit('Where');
    });
    $('#ab').accordion({ collapsible : true, active : 'none' });
});
