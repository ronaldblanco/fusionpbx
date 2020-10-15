--	FusionPBX
--	Version: MPL 1.1

--	The contents of this file are subject to the Mozilla Public License Version
--	1.1 (the "License"); you may not use this file except in compliance with
--	the License. You may obtain a copy of the License at
--	http://www.mozilla.org/MPL/

--	Software distributed under the License is distributed on an "AS IS" basis,
--	WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
--	for the specific language governing rights and limitations under the
--	License.

--	The Original Code is FusionPBX

--	The Initial Developer of the Original Code is
--	Mark J Crane <markjcrane@fusionpbx.com>
--	Portions created by the Initial Developer are Copyright (C) 2014-2019
--	the Initial Developer. All Rights Reserved.
--get the variables


if (session:ready()) then

    destination_number = session:getVariable("destination_number")
    non_local_number_length = tonumber(session:getVariable("non_local_number_length") or "6")

    --includes
    local log = require "resources.functions.log".is_local

    if (#destination_number < non_local_number_length) then
        log("Call is not considered local, exiting")
        do return end
    end

    --set defaults
    expire = {}
    expire["is_local"] = "3600"

    outbound_caller_id_name = session:getVariable("outbound_caller_id_name")
    outbound_caller_id_number = session:getVariable("outbound_caller_id_number")
    domain_name = session:getVariable("domain_name")

    local cache = require "resources.functions.cache"
    --prepare the api object
    api = freeswitch.API()

    --define the trim function
    require "resources.functions.trim"

    --set the cache key
    key = "app:dialplan:outbound:is_local:" .. destination_number .. "@" .. domain_name

    --get the destination number
    value, err = cache.get(key)

    if (not value) then

        log.notice("Cannot get " .. key .. " from cache with " .. err)
        --connect to the database
        local Database = require "resources.functions.database";
        local dbh = Database.new('system');

        --select data from the database
        local sql = "SELECT v_destinations.destination_number, v_destinations.destination_context "
        sql = sql .. "FROM v_destinations "
        sql = sql .. "JOIN v_domains"
        sql = sql .. " ON v_destinations.domain_uuid = v_domains.domain_uuid "
        sql = sql .. "WHERE"
        sql = sql .. " v_destinations.destination_number LIKE '%" .. destination_number .. "'"
        sql = sql .. " AND v_destinations.destination_type = 'inbound'"
        sql = sql .. " AND v_destinations.destination_enabled = 'true'"
        sql = sql .. " AND v_domains.domain_enabled = 'true'"

        if (debug["sql"]) then
            log.notice("SQL:" .. sql)
        end

        dbh:query(sql, params, function(row)

            --set the local variables
                destination_context = row.destination_context
                actual_destination_number = row.destination_number

            --set the cache
                key = "app:dialplan:outbound:is_local:" .. destination_number .. "@" .. domain_name
                value = "destination_number=" .. actual_destination_number .. "&destination_context=" .. destination_context
                ok, err = cache.set(key, value, expire["is_local"])

            --log the result
                log.notice(actual_destination_number .. " XML " .. destination_context .. " source: database")

            --set the outbound caller id
                if (outbound_caller_id_name ~= nil) then
                    session:execute("set", "caller_id_name="..outbound_caller_id_name)
                    session:execute("set", "effective_caller_id_name="..outbound_caller_id_name)
                end

                if (outbound_caller_id_number ~= nil) then
                    session:execute("set", "caller_id_number="..outbound_caller_id_number)
                    session:execute("set", "effective_caller_id_number="..outbound_caller_id_number)
                end

            --transfer the call
                session:transfer(actual_destination_number, "XML", destination_context)
                do return end
        end)

        -- set the cache not to ask database again
        key = "app:dialplan:outbound:is_local:" .. destination_number .. "@" .. domain_name
        value = "none"
        ok, err = cache.set(key, value, expire["is_local"])

        do return end

    end

    --cache is found
    --check cache is not none
    if (value == "none") then
        log.notice("Call is not local. Source: cache")
        do return end
    end

    --add the function
    require "resources.functions.explode";

    --define the array/table
    local var = {}

    --parse the cache
    key_pairs = explode("&", value)

    for k,v in pairs(key_pairs) do
        f = explode("=", v)
        key = f[1]
        value = f[2]
        var[key] = value
    end

    --set the outbound caller id
    if (outbound_caller_id_name ~= nil) then
        session:execute("set", "caller_id_name="..outbound_caller_id_name)
        session:execute("set", "effective_caller_id_name="..outbound_caller_id_name)
    end

    if (outbound_caller_id_number ~= nil) then
        session:execute("set", "caller_id_number="..outbound_caller_id_number)
        session:execute("set", "effective_caller_id_number="..outbound_caller_id_number)
    end

    --send to the console
    log.notice(value .. " source: cache")

    --transfer the call
    session:transfer(var["destination_number"], "XML", var["destination_context"])

end