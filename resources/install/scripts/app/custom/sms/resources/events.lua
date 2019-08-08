--
--      Version: MPL 1.1
--
--      The contents of this file are subject to the Mozilla Public License Version
--      1.1 (the "License"); you may not use this file except in compliance with
--      the License. You may obtain a copy of the License at
--      http://www.mozilla.org/MPL/
--
--      Software distributed under the License is distributed on an "AS IS" basis,
--      WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--      for the specific language governing rights and limitations under the
--      License.
--
--      The Original Code is FusionPBX
--
--      The Initial Developer of the Original Code is
--      Mark J Crane <markjcrane@fusionpbx.com>
--      Copyright (C) 2018


-- Start the script
	--	<!-- Subscribe to events -->
	--	<hook event="CUSTOM" subclass="SMS::SEND_MESSAGE" script="app/messages/resources/events.lua"/>

--prepare the api object
	api = freeswitch.API()

--define the functions
	require "resources.functions.trim"
	require "resources.functions.explode"
	require "resources.functions.base64"

--include the database class
	local Database = require "resources.functions.database"

--set debug
	debug["sql"] = false

	function get_settings_parameter(settings, parameter)
		if (settings['sms'][parameter] ~= nil) then
			if (settings['sms'][parameter]['text'] ~= nil) then
				return settings['sms'][parameter]['text']
			end
		end
		return nil
	end

--get the events
	--serialize the data for the console
	--freeswitch.consoleLog("notice","[events] " .. event:serialize("xml") .. "\n");
	--freeswitch.consoleLog("notice","[evnts] " .. event:serialize("json") .. "\n");

--intialize settings
	--from_user = '';

--get the event variables
	uuid               = event:getHeader("Core-UUID")
	from_user          = event:getHeader("from_user")
	from_host          = event:getHeader("from_host")
	to_user            = event:getHeader("to_user")
	to_host            = event:getHeader("to_host")
	content_type       = event:getHeader("type")
	sms_message_text   = event:getBody()

--set required variables
	if (from_user ~= nil and from_host ~= nil) then
		sms_message_from   = from_user .. '@' .. from_host
	end
	if (to_user ~= nil and to_host ~= nil) then
		sms_message_to     = to_user .. '@' .. to_host
	end
	sms_type       = 'sms';

--connect to the database
	dbh = Database.new('system');

--exits the script if we didn't connect properly
	assert(dbh:connected());

--set debug
	debug["sql"] = true;

--include json library
	local json
	if (debug["sql"]) then
		json = require "resources.functions.lunajson"
	end

--check if the from user exits
	if (from_user ~= nil and from_host ~= nil) then
		cmd = "user_exists id ".. from_user .." "..from_host
		freeswitch.consoleLog("notice", "[sms][from] user exists " .. cmd .. "\n")
		from_user_exists = api:executeString(cmd)
	else
		from_user_exists  = 'false'
	end

--check if the to user exits
	if (to_user ~= nil and to_host ~= nil) then
		cmd = "user_exists id ".. to_user .." "..to_host
		freeswitch.consoleLog("notice", "[sms][to] user exists " .. cmd .. "\n")
		to_user_exists = api:executeString(cmd)
	else
		to_user_exists = 'false'
	end

--add the message
	if (from_user_exists == 'true') then
		--set the direction
		sms_message_direction = 'send'

		--get the from user_uuid
		cmd = "user_data ".. from_user .."@"..from_host.." var domain_uuid"
		domain_uuid = trim(api:executeString(cmd))

		--sql statement
		sql = "INSERT INTO v_sms_messages "
		sql = sql .."( "
		sql = sql .."domain_uuid, "
		sql = sql .."sms_message_uuid, "
		sql = sql .."sms_message_timestamp, "
		sql = sql .."sms_message_from, ";
		sql = sql .."sms_message_to, "
		sql = sql .."sms_message_direction, "
		sql = sql .."sms_message_text "
		sql = sql ..") "
		sql = sql .."VALUES ( "
		sql = sql ..":domain_uuid, "
		sql = sql ..":sms_message_uuid, "
		sql = sql .."now(), "
		sql = sql ..":sms_message_from, ";
		sql = sql ..":sms_message_to, "
		sql = sql ..":sms_message_direction, "
		sql = sql ..":sms_message_text, "
		sql = sql ..") ";

		--set the parameters
		local params= {}
		params['domain_uuid']            = domain_uuid
		params['sms_message_uuid']       = api:executeString("create_uuid")
		params['sms_message_from']       = (from_user ~= nil and string.len(from_user) > 0) and from_user or "NA"
		params['sms_message_to']         = (to_user ~= nil and string.len(to_user) > 0) and to_user or "NA"
		params['sms_message_direction']  = sms_message_direction
		params['sms_message_text']       = sms_message_text

		--show debug info
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[sms] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
		end

		--run the query
		dbh:query(sql, params);
	end
	
	-- Possible duplicate by design here. To have separate SMS for each user.
	if (to_user_exists == 'true') then

		--set the direction
		sms_message_direction = 'receive';

		--get the from user_uuid
		cmd = "user_data ".. to_user .."@"..to_host.." var domain_uuid";
		domain_uuid = trim(api:executeString(cmd));

		--sql statement
		sql = "INSERT INTO v_sms_messages "
		sql = sql .."( "
		sql = sql .."domain_uuid, "
		sql = sql .."sms_message_uuid, "
		sql = sql .."sms_message_timestamp, "
		sql = sql .."sms_message_from, ";
		sql = sql .."sms_message_to, "
		sql = sql .."sms_message_direction, "
		sql = sql .."sms_message_text "
		sql = sql ..") "
		sql = sql .."VALUES ( "
		sql = sql ..":domain_uuid, "
		sql = sql ..":sms_message_uuid, "
		sql = sql .."now(), "
		sql = sql ..":sms_message_from, ";
		sql = sql ..":sms_message_to, "
		sql = sql ..":sms_message_direction, "
		sql = sql ..":sms_message_text, "
		sql = sql ..") ";

		--set the parameters
		local params= {}
		params['domain_uuid']            = domain_uuid
		params['sms_message_uuid']       = api:executeString("create_uuid")
		params['sms_message_from']       = (from_user ~= nil and string.len(from_user) > 0) and from_user or "NA"
		params['sms_message_to']         = (to_user ~= nil and string.len(to_user) > 0) and to_user or "NA"
		params['sms_message_direction']  = sms_message_direction
		params['sms_message_text']       = sms_message_text

		--show debug info
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[sms] SQL: " .. sql .. "; params:" .. json.encode(params) .. "\n");
		end

		--run the query
		dbh:query(sql, params);

	else

		-- get settings needed to send the message
		require "resources.functions.settings";
		-- This means, that we have from_user

		-- Get routing rules for this message type.
		sql =        "SELECT sms_routing_source, "
		sql = sql .. "sms_routing_destination, "
		sql = sql .. "sms_routing_target_details"
		sql = sql .. " FROM v_sms_routing WHERE"
		sql = sql .. " domain_uuid = '" .. domain_uuid .. "'"
		sql = sql .. " AND sms_routing_target_type = 'carrier'"
		sql = sql .. " AND sms_routing_enabled = 'true'"

		--show debug info
		if (debug["sql"]) then
			freeswitch.consoleLog("notice", "[sms] SQL: " .. sql .. "\n")
		end

		local routing_patterns = {}
        dbh:query(sql, function(row)
            table.insert(routing_patterns, row)
		end);
		
		local sms_carrier

		for _, routing_pattern in pairs(routing_patterns) do
			sms_routing_source = routing_pattern['sms_routing_source']
			sms_routing_destination = routing_pattern['sms_routing_destination']

			if (from_user:find(sms_routing_source) and to_user:find(sms_routing_destination)) then
				sms_carrier = routing_pattern['sms_routing_target_details']
				freeswitch.consoleLog("notice", "[sms] Using carrier for this SMS:" .. sms_carrier .. "\n")

				return
			end
		end

		if (sms_carrier == nil) then
			freeswitch.consoleLog("notice", "[sms] Cannot find carrier for this SMS: From:" .. sms_message_from .. "  To: " .. sms_message_to .. " \n")
			do return end
		end

		settings = settings(domain_uuid)
		if (settings['sms'] ~= nil) then
			request_type = get_settings_parameter(settings, sms_carrier .. "_request_type") -- internal or curl. as of now - internal used
			sms_carrier_url = get_settings_parameter(settings, sms_carrier .. "_url")
			sms_carrier_user = get_settings_parameter(settings, sms_carrier .. "_user")
			sms_carrier_password = get_settings_parameter(settings, sms_carrier .. "_password")
			sms_carrier_body = get_settings_parameter(settings, sms_carrier .. "_body")
			sms_carrier_content_type = get_settings_parameter(settings, sms_carrier .. "_content_type") or "application/json"
			sms_carrier_method =  get_settings_parameter(settings, sms_carrier .. "_method") or 'post'
			
			-- Get all changes in URL line. TODO in the future. For now - hardcode it
			-- for word in sms_carrier_url:gmatch("{%a+}") do 
			--    print(word) 
			-- end
		end

		--get the sip user outbound_caller_id
		if (from_user ~= nil and from_host ~= nil) then
			cmd = "user_data ".. from_user .."@"..from_host.." var outbound_caller_id_number"
			from = trim(api:executeString(cmd));
		else
			from = '';
		end

		--replace variables for their value
		if (sms_carrier_url) then
			sms_carrier_url = sms_carrier_user and sms_carrier_url:gsub("${user}", sms_carrier_user) or sms_carrier_url
			sms_carrier_url = sms_carrier_password and sms_carrier_url:gsub("${password}", sms_carrier_password) or sms_carrier_url
			sms_carrier_url = sms_carrier_url:gsub("${from}", from)
			sms_carrier_url = sms_carrier_url:gsub("${to}", to_user)
			sms_carrier_url = sms_carrier_url:gsub("${text}", sms_message_text)
		end

		if (sms_carrier_body) then
			sms_carrier_body = sms_carrier_user and sms_carrier_body:gsub("${user}", sms_carrier_user) or sms_carrier_body
			sms_carrier_body = sms_carrier_password and sms_carrier_body:gsub("${password}", sms_carrier_password) or sms_carrier_body
			sms_carrier_body = sms_carrier_body:gsub("${from}", from)
			sms_carrier_body = sms_carrier_body:gsub("${to}", to_user)
			sms_carrier_body = sms_carrier_body:gsub("${text}", sms_message_text)
		end
			
		
		if (to_user == nil) then
			freeswitch.consoleLog("notice", "[message] To is nil. Not sending... \n");
			return
		end

		-- Send to the provider using curl
		if (request_type == "internal") then
			cmd = "curl " .. sms_carrier_url .. " content-type " .. sms_carrier_content_type .. " " .. sms_carrier_method .. " " .. sms_carrier_body
			api:executeString(cmd)
		end
	end