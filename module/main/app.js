/*
	Copyright 2014 - Mhd Sulhan
	Authors:
		- mhd.sulhan (m.shulhan@gmail.com)
*/
//{{{ store: Asset Type
Ext.define ("Jx.app.store.Asset.Type", {
	extend		:"Jx.StoreRest"
,	fields		:
	[{
		name		:"id"
	,	type		:"int"
	},{
		name		:"name"
	,	type		:"string"
	}]

,	config		:
	{
		url			:""
	,	storeId		:"Reference_Asset_Type"
	,	autoDestroy	:false
	}

,	constructor	:function (config)
	{
		var opts = {};

		Ext.merge (opts, this.config);
		Ext.merge (opts, config);

		if ("" === opts.url) {
			opts.url = Jx.generateModDir (this.storeId);
		}

		this.callParent ([opts]);
	}
});
//}}}
//{{{ store: Asset Procurement
Ext.define ("Jx.app.store.Asset.Procurement", {
	extend		:"Jx.StoreRest"
,	fields		:
	[{
		name		:"id"
	,	type		:"int"
	},{
		name		:"name"
	,	type		:"string"
	}]

,	config		:
	{
		url			:""
	,	autoDestroy	:false
	,	storeId		:"Reference_Asset_Procurement"
	}

,	constructor	:function (config)
	{
		var opts = {};

		Ext.merge (opts, this.config);
		Ext.merge (opts, config);

		if ("" === opts.url) {
			opts.url = Jx.generateModDir (this.storeId);
		}

		this.callParent ([opts]);
	}
});
//}}}
//{{{ store: Asset Status
Ext.define ("Jx.app.store.Asset.Status", {
	extend		:"Jx.StoreRest"
,	fields		:
	[{
		name		:"id"
	,	type		:"int"
	},{
		name		:"name"
	,	type		:"string"
	}]

,	config		:
	{
		url			:""
	,	autoDestroy	:false
	,	storeId		:"Reference_Asset_Status"
	}

,	constructor	:function (config)
	{
		var opts = {};

		Ext.merge (opts, this.config);
		Ext.merge (opts, config);

		if ("" === opts.url) {
			opts.url = Jx.generateModDir (this.storeId);
		}

		this.callParent ([opts]);
	}
});
//}}}
//{{{ store: Asset Location
Ext.define ("Jx.app.store.Asset.Location", {
	extend		:"Jx.StoreRest"
,	fields		:
	[{
		name		:"id"
	,	type		:"int"
	},{
		name		:"name"
	,	type		:"string"
	}]

,	config		:
	{
		url			:""
	,	autoDestroy	:false
	,	storeId		:"Reference_Asset_Location"
	}

,	constructor	:function (config)
	{
		var opts = {};

		Ext.merge (opts, this.config);
		Ext.merge (opts, config);

		if ("" === opts.url) {
			opts.url = Jx.generateModDir (this.storeId);
		}

		this.callParent ([opts]);
	}
});
//}}}
//{{{ store: Asset
Ext.define ("Jx.app.store.Asset", {
	extend		:"Jx.StoreRest"
,	fields		:
	[
		"id"
	,	"type_id"
	,	"merk"
	,	"model"
	,	"sn"
	,	"barcode"
	,	"service_tag"
	,	"label"
	,	"detail"
	,	"warranty_date"
	,	"warranty_length"
	,	"warranty_info"
	,	"procurement_id"
	,	"company"
	,	"price"
	,	"status_id"
	,	"_user_id"
	,	"location_id"
	,	"location_detail"
	,	"maintenance_info"
	,	"table_id"
	]

,	config		:
	{
		url			:""
	,	autoDestroy	:false
	,	storeId		:"Asset"
	}

,	constructor	:function (config)
	{
		var opts = {};

		Ext.merge (opts, this.config);
		Ext.merge (opts, config);

		if ("" === opts.url) {
			opts.url = Jx.generateModDir (this.storeId);
		}

		this.callParent ([opts]);
	}
});

//}}}
Jx.app.store.Asset				= Ext.create ("Jx.app.store.Asset");

Jx.app.store.Asset.Type			= Ext.create ("Jx.app.store.Asset.Type");
Jx.app.store.Asset.Procurement	= Ext.create ("Jx.app.store.Asset.Procurement");
Jx.app.store.Asset.Status		= Ext.create ("Jx.app.store.Asset.Status");
Jx.app.store.Asset.Location		= Ext.create ("Jx.app.store.Asset.Location");