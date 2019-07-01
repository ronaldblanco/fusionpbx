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
	api = freeswitch.API();

--define the functions
	require "resources.functions.trim"
	require "resources.functions.explode"
	require "resources.functions.base64"

--include the database class
	local Database = require "resources.functions.database"

--set debug
	debug["sql"] = false

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
		sql = sql .. ""

		settings = settings(domain_uuid);
		if (settings['sms'] ~= nil) then
			http_method = '';
			if (settings['sms']['http_method'] ~= nil) then
				if (settings['message']['http_method']['text'] ~= nil) then
					http_method = settings['message']['http_method']['text'];
				end
			end

			http_content_type = '';
			if (settings['message']['http_content_type'] ~= nil) then
				if (settings['message']['http_content_type']['text'] ~= nil) then
					http_content_type = settings['message']['http_content_type']['text'];
				end
			end

			http_destination = '';
			if (settings['message']['http_destination'] ~= nil) then
				if (settings['message']['http_destination']['text'] ~= nil) then
					http_destination = settings['message']['http_destination']['text'];
				end
			end

			http_auth_enabled = 'false';
			if (settings['message']['http_auth_enabled'] ~= nil) then
				if (settings['message']['http_auth_enabled']['boolean'] ~= nil) then
					http_auth_enabled = settings['message']['http_auth_enabled']['boolean'];
				end
			end

			http_auth_type = '';
			if (settings['message']['http_auth_type'] ~= nil) then
				if (settings['message']['http_auth_type']['text'] ~= nil) then
					http_auth_type = settings['message']['http_auth_type']['text'];
				end
			end

			http_auth_user = '';
			if (settings['message']['http_auth_user'] ~= nil) then
				if (settings['message']['http_auth_user']['text'] ~= nil) then
					http_auth_user = settings['message']['http_auth_user']['text'];
				end
			end

			http_auth_password = '';
			if (settings['message']['http_auth_password'] ~= nil) then
				if (settings['message']['http_auth_password']['text'] ~= nil) then
					http_auth_password = settings['message']['http_auth_password']['text'];
				end
			end
		end

		--get the sip user outbound_caller_id
		if (from_user ~= nil and from_host ~= nil) then
			cmd = "user_data ".. from_user .."@"..from_host.." var outbound_caller_id_number";
			from = trim(api:executeString(cmd));
		else
			from = '';
		end

		--replace variables for their value
		http_destination = http_destination:gsub("${from}", from);
		
		--send to the provider using curl
		if (to_user ~= nil) then
			cmd = [[curl ]].. http_destination ..[[ ]]
			cmd = cmd .. [[-H "Content-Type: ]]..http_content_type..[[" ]];
			if (http_auth_type == 'basic') then
				cmd = cmd .. [[-H "Authorization: Basic ]]..base64.encode(http_auth_user..":"..http_auth_password)..[[" ]];
			end
			cmd = cmd .. [[-d '{"to":"]]..to_user..[[","text":"]]..message_text..[["}']]
			result = api:executeString("system "..cmd);
			--status = os.execute (cmd);

			--debug - log the command
			freeswitch.consoleLog("notice", "[message] " .. cmd.. "\n");
		end

	end