<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'speedtest');
$eqLogics = eqLogic::byType('speedtest');
$plugin = plugin::byId('speedtest');
?>

<div class="row row-overflow">
	<div class="col-xs-12 eqLogicThumbnailDisplay">
		<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
        	<div class="cursor eqLogicAction logoSecondary" data-action="add"  >
                <i class="fas fa-plus-circle"></i>
                <br>
                <span>{{Ajouter}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
              <i class="fas fa-wrench"></i>
            <br>
            <span >{{Configuration}}</span>
            </div>            
		</div>			
		<legend>{{Mes Equipements}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<div class="eqLogicThumbnailContainer">
		<?php
			foreach ($eqLogics as $eqLogic) {				
				$opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
				echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" >';
				echo '<img src="' . $plugin->getPathImgIcon() . '" height="105" width="95" />';
				echo "<br>";
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
				echo '</div>';
			 }
		?>
		</div>
	</div>
	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default eqLogicAction btn-sm roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#infotab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Configuration}}</a></li>
			<li role="presentation"><a href="#infocmd" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">  
            	<form class="form-horizontal">
                	<fieldset>
					<br />
						<div class="form-group">
							<label class="col-md-2 control-label">{{Nom de l'équipement speedtest}}</label>
							<div class="col-sm-3">
								<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
								<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
							</div>
						</div>
						<div class="form-group">
							<label class="col-md-2 control-label" >{{Objet parent}}</label>
							<div class="col-sm-3">
								<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
									<option value="">{{Aucun}}</option>
									<?php
										foreach (jeeObject::all() as $object) {
											echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
										}
									?>
							   </select>
						    </div>
					   	</div>
						<div class="form-group">
							<label class="col-md-2 control-label">{{Catégorie}}</label>
							<div class="col-md-8">
							<?php
								foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
									echo '<label class="checkbox-inline">';
									echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
									echo '</label>';
								}
							?>
							</div>
						</div>           
                		<div class="form-group">
                  			<label class="col-md-2 control-label" >{{Activer}}</label>
						    <div class="col-md-1">
								<input type="checkbox" class="eqLogicAttr checkbox-inline" data-label-text="{{Activer}}" data-l1key="isEnable" checked/>
						    </div>
                  			<label class="col-md-2 control-label prog_visible" >{{Visible}}</label>
						    <div class="col-md-1 prog_visible">
								<input type="checkbox" class="eqLogicAttr checkbox-inline" data-label-text="{{Visible}}" data-l1key="isVisible" checked/>
						    </div>
               			</div>
        			</fieldset>
        		</form>
        	</div>
			<div role="tabpanel" class="tab-pane" id="infotab">
				<br />
				<form class="form-horizontal">
					<div class="form-group">
						<label class="col-md-2 control-label">{{Activer Cron}}</label>
						<div class="col-md-1">
							<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="autCron"/>
						</div>
						<div id="cron_speedtest">
							<label class="col-md-2 control-label" >{{Fréquence de rafraichissement des données}}</label>
							<div class="col-md-2">
								<input id="valueCron" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="refreshCron"/>
							</div>
							<div class="col-sm-1">
								<i class="fas fa-question-circle cursor floatright" id="bt_cronGenerator"></i>
							</div>
						</div>                     	
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">{{Id serveur}}</label>
						<div class="col-md-1">
							<input type="text"  class="eqLogicAttr configuration form-control" data-l1key="configuration" data-l2key="server_id" placeholder="Server id"/>
						</div> 
						<div class="col-md-1"> 
							<label class=" ontrol-label" ><a style="text-decoration: underline"  href="http://www.speedtestserver.com/">{{Liste des serveurs}}</a></label>  
						</div>       
					</div> 
					<br/>                        
					<div class="form-group">
						<label class="col-md-2 control-label">{{Widget alternatif}}</label>
						<div class="col-md-1">
							<input type="checkbox" class="eqLogicAttr widgetType" data-l1key="configuration" data-l2key="autAlt"/>
						</div>                   	
					</div>
					<div class="form-group">
						<label class="col-md-2 control-label">{{Widget Betâ}}</label>
						<div class="col-md-1">
							<input type="checkbox" class="eqLogicAttr widgetType" data-l1key="configuration" data-l2key="autAltBeta" />
						</div>                   	
					</div>                            
				</form>                      
			</div>
			<div role="tabpanel" class="tab-pane" id="infocmd">  
				<br />
				<div class="col-md-1"></div>
				<table style="width: 600px" id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th>{{Id}}</th>
							<th>{{Nom}}</th>
							<th>{{Options}}</th>
							<th>{{Actions}}</th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>             
			</div>
		</div>
	</div>
</div>

<?php include_file('desktop', 'speedtest', 'js', 'speedtest');?>
<?php include_file('core', 'plugin.template', 'js');?>