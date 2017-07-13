-- modify MOH from local_stream to file_stream
require "resources.functions.local_to_file_stream"

if (session:ready()) then
    hold_music = session:getVariable("hold_music")
    --freeswitch.consoleLog("notice", "[FIX_MOH] Got moh "..hold_music.."\n")
    hold_music = local_to_file_stream(hold_music)
    session:setVariable("hold_music", hold_music)

    transfer_ringback = session:getVariable("transfer_ringback")
    transfer_ringback = local_to_file_stream(transfer_ringback)
    session:setVariable("transfer_ringback", transfer_ringback)

    ringback = session:getVariable("ringback")
    ringback = local_to_file_stream(ringback)
    session:setVariable("ringback", ringback)
    --freeswitch.consoleLog("notice", "[FIX_MOH] Set moh "..hold_music.."\n")
    session:setVariable("fix_moh", "true")
end
