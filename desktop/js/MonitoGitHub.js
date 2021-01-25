
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */


/*
* Permet la réorganisation des commandes dans l'équipement
*/
$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

/*
* Fonction permettant l'affichage des commandes dans l'équipement
*/
function addCmdToTable(_cmd) {
  if (!isset(_cmd)) {
     var _cmd = {configuration: {}};
   }
   if (!isset(_cmd.configuration)) {
     _cmd.configuration = {};
   }
   var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
   tr += '<td style="min-width:50px;width:70px;">';
   tr += '<span class="cmdAttr" data-l1key="id"></span>';
   tr += '</td>';
   tr += '<td style="min-width:300px;width:350px;">';
   tr += '<div class="row">';
   tr += '<div class="col-xs-7">';
   tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom de la commande}}">';
  // tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="{{Commande information liée}}">';
  // tr += '<option value="">{{Aucune}}</option>';
  // tr += '</select>';
   tr += '</div>';
   tr += '<div class="col-xs-5">';
   tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fas fa-flag"></i> {{Icône}}</a>';
   tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
   tr += '</div>';
   tr += '</div>';
   tr += '</td>';
   tr += '<td style="min-width:75px;width:120px;">';
  // tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
  tr += '<span class="type" type="' + init(_cmd.type) + '">'+_cmd.type+' | '+_cmd.subType+'</span>';

  // tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
   tr += '</td>';
   tr += '<td style="min-width:50px;width:100px;">';
   tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></div> ';
   tr += '</td>';
   tr += '<td style="min-width:50px;width:170px;">';
   tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></div> ';
   //tr += '<div><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></div>';
   tr += '</td>';
   /*tr += '<td style="min-width:180px;">';
   tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min.}}" title="{{Min.}}" style="width:30%;display:inline-block;"/> ';
   tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max.}}" title="{{Max.}}" style="width:30%;display:inline-block;"/> ';
   tr += '<input class="cmdAttr form-control input-sm" data-l1key="unite" placeholder="{{Unité}}" title="{{Unité}}" style="width:30%;display:inline-block;"/>';
   tr += '</td>';*/
   tr += '<td>';
   if (is_numeric(_cmd.id)) {
     tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> ';
     tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>';
   }
   tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
   tr += '</td>';
   tr += '</tr>';
   $('#table_cmd tbody').append(tr);
   var tr = $('#table_cmd tbody tr').last();
   jeedom.eqLogic.builSelectCmd({
     id:  $('.eqLogicAttr[data-l1key=id]').value(),
     filter: {type: 'info'},
     error: function (error) {
       $('#div_alert').showAlert({message: error.message, level: 'danger'});
     },
     success: function (result) {
       tr.find('.cmdAttr[data-l1key=value]').append(result);
       tr.setValues(_cmd, '.cmdAttr');
       jeedom.cmd.changeType(tr, init(_cmd.subType));
     }
   });

 }

// hide la programmation du cron.
$(".eqLogicAttr[data-l2key='freq']").on('change', function () {
  if($(this).val() != 'prog'){
    $(".mgh-actu-auto").hide();
  }else{
    $(".mgh-actu-auto").show();
  }
  if($(this).val() != 'manual'){
    $(".warning_manualupdate").hide();
  }else{
    $(".warning_manualupdate").show();
  }
});



$(".eqLogicAttr[data-l2key='owner']").on('change', function () {
  updateMGHPathURL();
});
$(".eqLogicAttr[data-l2key='repo']").on('change', function () {
  updateMGHPathURL();
});
$(".eqLogicAttr[data-l2key='path']").on('change', function () {
  updateMGHPathURL();
});

function updateMGHPathURL(){

  var pathGH= "https://github.com/";

  var addP=$(".eqLogicAttr[data-l2key='owner']").val() ;
  if(addP != "" ){
    pathGH+=addP+"/";
  }
  addP=$(".eqLogicAttr[data-l2key='repo']").val() ;
  if(addP != "" ){
    pathGH+=addP+"/";
  }
  addP=$(".eqLogicAttr[data-l2key='path']").val() ;
  if(addP != "" ){
    //remplacement du premier / si 
    addP=addP.replace(/[\\\/]+/,'');
    //if (addP.slice(-1)!="/"){addp+="/";}

    pathGH+=addP;
  }
 

  
  $(".mgh-repos-url").html('<a href="'+pathGH+'" target="_blank">'+pathGH+'</a>');
  // type d'équipement
  var typeSurv='';
  if($(".eqLogicAttr[data-l2key='path']").val()==''){
    typeSurv = 'repos';
  }else if($(".eqLogicAttr[data-l2key='path']").val().substr(-1)=='/'){
      typeSurv = 'folder';
  }else{
      typeSurv = 'file';
  }

  console.log("type equipement "+typeSurv); 
  $(".eqLogicAttr[data-l2key='typesurvey']").val(typeSurv);

}

