/*
	Copyright 2013 x10c-lab.com
	Authors:
		- mhd.sulhan (sulhan@x10c-lab.com)
*/

function JxSystemGroup ()
{
	this.id		= "SystemGroup";
	this.dir	= _g_module_dir +"/"+ this.id;

	this.store	= Ext.create ("Jx.StorePaging", {
		storeId		:this.id +"Store"
	,	url			:this.dir +"/data.jsp"
	,	fields		:
		[
			"id"
		,	"name"
		,	"password"
		]
	})

,	this.panel	= Ext.create ("Jx.GridPaging", {
		id			:this.id
	,	title		:"System Group"
	,	autoDestroy	:true
	,	store		:this.store
	,	columns		:
		[{
			header		:"Group name"
		,	dataIndex	:"name"
		,	flex		:1
		}]
	})

,	this.doRefresh	= function (perm)
	{
		this.panel.doRefresh (perm);
	}
};

/* moduleName = className */
var SystemGroup = new JxSystemGroup ();