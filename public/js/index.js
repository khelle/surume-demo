/**
 * Helpers
 */
;(function(undefined) {

    var $D = function(id) {
        return document.querySelector(id);
    };

    this.$D = $D;

}).call(window);

/**
 * WsConnection
 */
;(function(undefined) {

    var WsConnection = function() {
        Object.defineProperty(this, 'flags', {
            value: {
                isConnected: false
            },
            writable: true
        });
        Object.defineProperty(this, 'ws', {
            value: null,
            writable: true
        });
    };

    this.WsConnection = WsConnection;

}).call(window);

/**
 * WsSocket
 */
;(function(undefined) {

    var WsSocket = function() {
        Object.defineProperty(this, 'connection', {
            value: new WsConnection()
        });
    };

    WsSocket.prototype.connect = function() {
        if (WebSocket === undefined)
        {
            throw new Error("WsConnection.connect() : Browser does not support WebSocket object.");
        }
        else if (this.connection.flags.isConnected)
        {
            throw new Error("WsConnection.connect() : Connection is already created. Disconnect before creating a new one.");
        }

        try
        {
            this.connection.ws = new WebSocket("ws://localhost:4080/chat");
        }
        catch (err)
        {
            this.onError(this.connection.ws, err);
        }

        var sock = this;
        this.connection.ws.onmessage = function(message) {
            return sock.onMessage(sock, message);
        };
        this.connection.ws.onopen = function() {
            return sock.onOpen(sock);
        };
        this.connection.ws.onclose = function() {
            return sock.onClose(sock);
        };
        this.connection.ws.onerror = function(err) {
            return sock.onError(sock, err);
        };
    };

    WsSocket.prototype.disconnect = function() {
        if (!this.connection.flags.isConnected)
        {
            return;
        }
        this.connection.ws.close();
    };

    WsSocket.prototype.send = function(message) {
        if (!this.connection.flags.isConnected)
        {
            return;
        }
        this.connection.ws.send(message);
    };

    WsSocket.prototype.onMessage = function(sock, message) {
        this.message(sock, message);
    };

    WsSocket.prototype.onOpen = function(sock) {
        this.connection.flags.isConnected = true;
        this.open(sock);
    };

    WsSocket.prototype.onClose = function(sock) {
        this.connection.flags.isConnected = false;
        this.close(sock);
    };

    WsSocket.prototype.onError = function(sock, err) {
        this.error(sock, err);
    };

    WsSocket.prototype.message = function(sock, message) {};

    WsSocket.prototype.open = function(sock) {};

    WsSocket.prototype.close = function(sock) {};

    WsSocket.prototype.error = function(sock, err) {};

    this.WsSocket = WsSocket;

}).call(window);

/**
 * Chat
 */
;(function(undefined) {

    var Chat = function() {};

    Chat.prototype.create = function() {
        $D('.chat-messagebox').innerHTML = '';
    };

    Chat.prototype.createMessage = function(sender, date, message) {
        var node,
            nameDiv,
            dateDiv,
            mssgDiv;

        node = document.createElement('div');
        node.className = 'chat-message';

        nameDiv = document.createElement('div');
        dateDiv = document.createElement('div');
        mssgDiv = document.createElement('div');

        nameDiv.className = 'name';
        nameDiv.innerHTML = sender;

        dateDiv.className = 'date';
        dateDiv.innerHTML = date;

        mssgDiv.className = 'message bubble';
        mssgDiv.innerHTML = message;

        node.appendChild(nameDiv);
        node.appendChild(dateDiv);
        node.appendChild(mssgDiv);

        $D('.chat-messagebox').appendChild(node);
    };

    this.Chat = Chat;

}).call(window);

/**
 * Application
 */
;(function(undefined) {

    var Application = function() {
        Object.defineProperty(this, 'panel', {
            value: {
                load: $D('#surume-panel'),
                appt: $D('#surume-app-panel'),
                text: $D('#chat-text')
            }
        });
        Object.defineProperty(this, 'ws', {
            value: new WsSocket()
        });
        Object.defineProperty(this, 'chat', {
            value: new Chat()
        });
        this.start();
    };

    Application.prototype.start = function() {
        console.log('Chat is being loaded...');
        var app = this;
        this.ws.open = function(sock) {
            app.showChat();
            app.chat.create();
            app.panel.text.onkeyup = function(e) {
                var key = e.keyCode;
                var text = '';

                if (key == 13)
                {
                    text = app.panel.text.value;
                    if (text !== "\n")
                    {
                        app.ws.send(text.slice(0, -1));
                        app.panel.text.value = '';
                    }
                }
                else
                {
                    return true;
                }
            };
            console.log('Chat opened.');
        };
        this.ws.close = function(sock) {
            app.hideChat();
            console.log('Chat closed.');
        };
        this.ws.message = function(sock, message) {
            var data = JSON.parse(message.data);
            app.chat.createMessage(data.name, data.date, data.mssg)
        };
        this.ws.error = function(sock, err) {
            console.log('Error' + err);
        };
        this.ws.connect();
    };

    Application.prototype.stop = function() {
        this.ws.close();
    };

    Application.prototype.showChat = function() {
        this.panel.load.style.display = 'none';
        this.panel.appt.style.display = 'block';
    };

    Application.prototype.hideChat = function() {
        this.panel.load.style.display = 'block';
        this.panel.appt.style.display = 'none';
    };

    this.Application = Application;

}).call(window);

var app = new Application();
