// row number suffix for input groups in rows
var yawpindex=0;
// grab template
var tpl=document.querySelector("#tpl");

// add row to table
function addRow(find,replace,acase) {
  // need to cloneNode everytime for each additional row
  var tnode = tpl.content.cloneNode(true)
  // get to the <td>'s
  for(var i=0;i<tnode.children[0].children.length;i++) {
    // first child of <td> is <input>
    for (var j=0;j<tnode.children[0].children[i].children.length;j++){
      var element = tnode.children[0].children[i].children[j];
      // fill inputs based on passed in params 
      switch (element.id) {
        case "yawpsearch_{yawpindex}":
          element.value=find?find:"";
          break;
        case "yawpreplace_{yawpindex}":
          element.value=replace?replace:"";
          break;
        case "yawpcase_{yawpindex}":
          element.checked=acase?true:false;
          break;
      }
      // add the row number suffix via template merge
      if (/{yawpindex}/.test(element.id))
        element.id=element.name=element.id.replace("{yawpindex}",yawpindex);
    }
  }
  // <tr> element gets its own row number suffix too
  tnode.children[0].id=tnode.children[0].id.replace("{yawpindex}",yawpindex++);
  // add row to table
  document.querySelector("#ttable").appendChild(tnode);
}

// click on x, lose the row based on row number/index
function removeRow(row) {
  document.querySelector("#ttable").removeChild(document.querySelector("#"+row.id.replace("yawpremove_","")));
}
