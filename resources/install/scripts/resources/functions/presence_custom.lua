-- publish info on custom presence server
local function publish_presence(user, state)
    local socket = require("socket.core")
    local tcp = assert(socket.tcp())
    tcp:connect(custom_presence_server['address'], custom_presence_server['port'])
    local msg = user.." "..state.."\n";
    tcp:send(msg)
end

return {
    publish_presence = publish_presence;
}