/*

Copyright (c) 2009 Anant Garg (anantgarg.com | inscripts.com)

This script may be used for non-commercial purposes only. For any
commercial purposes, please contact the author at 
anant.garg@inscripts.com

Changes and Chamilo Integration: Julio Montoya <gugli100@gmail.com>


THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.

*/

var windowFocus = true;
var username;
var chatHeartbeatCount  = 0;
var minChatHeartbeat    = 4000;
var maxChatHeartbeat    = 33000;
var chatHeartbeatTime   = minChatHeartbeat;
var originalTitle;
var blinkOrder          = 0;

var chatboxFocus        = new Array();
var newMessages         = new Array();
var newMessagesWin      = new Array();
var chatBoxes           = new Array();

var timer;
var user_status         = 0;
//var ajax_url = 'chat.php'; // This variable is loaded in the template/layout/head.tpl file

function set_user_status(status) {
    $.ajax({
        url: ajax_url+"?action=set_status",
        data: "status="+status,
        cache: false,        
        success: function(data) {        
        }
    });
    user_status = status;
}

$(document).ready(function() {
	originalTitle = document.title;
    
	startChatSession();    

	$([window, document]).blur(function() {
		windowFocus = false;
	}).focus(function(){
		windowFocus = true;
		document.title = originalTitle;
	});
	
	/* Live conditions */
    
    // User name header toogle
	$('#chatboxtitlemain').live('click', function() {        
        if (user_status == 1) {            
            stopChatHeartBeat();   
            $('.user_status_main').html(offline_button);
            $('#chatboxtitlemain').html(disconnect_lang);             
            set_user_status(0);      
        } else {            
            startChatHeartBeat();
            $('.user_status_main').html(online_button);
            $('#chatboxtitlemain').html(connect_lang);
            set_user_status(1);      
        }
	});    
	
	// User name header toogle
	$('.chatboxtitle').live('click', function(){
		chatbox = $(this).parents(".chatbox");
		var chat_id = chatbox.attr('id');
		chat_id = chat_id.split('_')[1];		
		toggleChatBoxGrowth(chat_id);
	});
	
	//Minimize button 
	$('.chatboxhead .togglelink').live('click', function(){
		var chat_id =  $(this).attr('rel');	
		toggleChatBoxGrowth(chat_id);		
	});	
	
	//Close button
	$('.chatboxhead .closelink').live('click', function(){			
		var chat_id =  $(this).attr('rel');	
		closeChatBox(chat_id);		
	});
  
});


function showChatConnect() {       
    if (user_status == 1) {
        button = online_button;  
        label = connect_lang;
    } else {        
        button = offline_button; 
        label = disconnect_lang;
    }
    $("<div />" ).attr("id","chatmain")
	.addClass("chatboxmain")
	.html('<div class="chatboxheadmain"><div class="user_status_main">'+button+'</div><div id="chatboxtitlemain">'+label+'</div><div class="chatboxoptions"></div><br clear="all"/></div></div>')
	.appendTo($( "body" ));       
}


/**
 * Start chat session
 */
function startChatSession() {
    /* fix bug BT#5728 whereby users cannot move to the next question in IE9 */
    if (typeof ajax_url != 'undefined') {
        $.ajax({
          url: ajax_url+"?action=startchatsession",
          cache: false,
          dataType: "json",
          success: function(data) {
            if (data) {
                username = data.me;    
                user_status = data.user_status;
                showChatConnect();
                if (user_status == 1) {            
                    startChatHeartBeat();   
                } else {
                    stopChatHeartBeat();   
                }
                
                $.each(data.items, function(my_user_id, user_items) {
                    my_items = user_items['items'];                
                    $.each(my_items, function(i, item) {
                    
                        if (item)    { // fix strange ie bug                        
                            //my_user_id        = item.f;                        
                            if ($("#chatbox_"+my_user_id).length <= 0) {                            
                                createChatBox(my_user_id, user_items.user_info.user_name, 1, user_items.user_info.online);
                            }
    
                            if (item.s == 1) {
                                //item.f = username;
                            }
                            if (item.s == 2) {
                                $("#chatbox_"+my_user_id+" .chatboxcontent").append('<div class="chatboxmessage"><span class="chatboxinfo">'+item.m+'</span></div>');
                            } else {
                                $("#chatbox_"+my_user_id+" .chatboxcontent").append('<div class="chatboxmessage">\n\
                                                                                        <span class="chatboxmessagefrom">'+item.username+':&nbsp;&nbsp;</span>\n\
                                                                                        <span class="chatboxmessagecontent">'+item.m+'</span></div>');
                            }
                        }
                    });
                            
                });
    
                for (i=0;i<chatBoxes.length;i++) {
                    my_user_id = chatBoxes[i];
                    $("#chatbox_"+my_user_id+" .chatboxcontent").scrollTop($("#chatbox_"+my_user_id+" .chatboxcontent")[0].scrollHeight);
                }
            }        
        }});
    }
}

function stopChatHeartBeat() {    
    clearInterval(timer);
    timer = null;
}
function startChatHeartBeat() {    
    timer = setInterval('chatHeartbeat();', chatHeartbeatTime);    
}

/*
 * Shows the user messages in all windows
 * 
 * Item array structure :
 * 
 * item.s = type of message: 1 = message, 2 = "sent at" string
 * item.m = message
 * item.f = from_user
 *
 **/
function chatHeartbeat() {
	var itemsfound = 0;
	
	if (windowFocus == false) {
 
		var blinkNumber  = 0;
		var titleChanged = 0;
		
		for (x in newMessagesWin) {
			if (newMessagesWin[x].status == true) {
				++blinkNumber;
				if (blinkNumber >= blinkOrder) {
					document.title = newMessagesWin[x].username+' says...';
					titleChanged = 1;
					break;	
				}
			}
		}
		
		if (titleChanged == 0) {
			document.title = originalTitle;
			blinkOrder = 0;
		} else {
			++blinkOrder;
		}

	} else {
		for (x in newMessagesWin) {
			newMessagesWin[x].status = false;
		}
	}

	for (x in newMessages) {
		if (newMessages[x] == true) {
			if (chatboxFocus[x] == false) {
				//FIXME: add toggle all or none policy, otherwise it looks funny
				$('#chatbox_'+x+' .chatboxhead').toggleClass('chatboxblink');
			}
		}
	}
	
	$.ajax({
		url: ajax_url+"?action=chatheartbeat",
		cache: false,
		dataType: "json",
		success: function(data) {			
		
			$.each(data.items, function(my_user_id, user_items) {
                my_items = user_items['items'];				
                
				$.each(my_items, function(i, item) {
					if (item)	{ // fix strange ie bug

						if ($("#chatbox_"+my_user_id).length <= 0) {
							createChatBox(my_user_id, user_items.user_info.user_name, 0, user_items.user_info.online);
						}
						if ($("#chatbox_"+my_user_id).css('display') == 'none') {
							$("#chatbox_"+my_user_id).css('display','block');
							restructureChatBoxes();
						}

						if (item.s == 1) {
							//item.f = username;
						}

						update_online_user(my_user_id, user_items.user_info.online);

						if (item.s == 2) {
							$("#chatbox_"+my_user_id+" .chatboxcontent").append('<div class="chatboxmessage"><span class="chatboxinfo">'+item.m+'</span></div>');
						} else {
							newMessages[my_user_id]		= {'status':true, 'username':item.username};
							newMessagesWin[my_user_id]	= {'status':true, 'username':item.username};						

							$("#chatbox_"+my_user_id+" .chatboxcontent").append('<div class="chatboxmessage">\n\
																				 <span class="chatboxmessagefrom">'+item.username+':&nbsp;&nbsp;</span>\n\
																				 <span class="chatboxmessagecontent">'+item.m+'</span></div>');
						}
						$("#chatbox_"+my_user_id+" .chatboxcontent").scrollTop($("#chatbox_"+my_user_id+" .chatboxcontent")[0].scrollHeight);
                        
                        if ($('#chatbox_'+my_user_id+' .chatboxcontent').css('display') == 'none') {
                            $('#chatbox_'+my_user_id+' .chatboxhead').toggleClass('chatboxblink');
                            
                        }
						itemsfound += 1;
					}
				});
			});

			chatHeartbeatCount++;

			if (itemsfound > 0) {
				chatHeartbeatTime = minChatHeartbeat;
				chatHeartbeatCount = 1;
			} else if (chatHeartbeatCount >= 10) {
				chatHeartbeatTime *= 2;
				chatHeartbeatCount = 1;
				if (chatHeartbeatTime > maxChatHeartbeat) {
					chatHeartbeatTime = maxChatHeartbeat;
				}
			}

			//timer = setTimeout('chatHeartbeat();',chatHeartbeatTime);
		}
	}); //ajax	
}

function closeChatBox(user_id) {
	$('#chatbox_'+user_id).css('display','none');
        restructureChatBoxes();	
        $.post(ajax_url+"?action=closechat", {chatbox: user_id} , function(data){	
	});
}


function restructureChatBoxes() {
	align = 0;
	for (x in chatBoxes) {
		user_id = chatBoxes[x];
		if ($("#chatbox_"+user_id).css('display') != 'none') {
			if (align == 0) {
				$("#chatbox_"+user_id).css('right', '245px');
			} else {
				width = (align)*(225+7)+20 + 225;
				$("#chatbox_"+user_id).css('right', width+'px');
			}
			align++;
		}
	}
}

/**
 * Function that fires the chat with an user (creates a chat bloclk)
 * @param int		user id 
 * @param string	user's firstname + lastname
 * @param status
 *
 **/
function chatWith(user_id, user_name, status) {
	createChatBox(user_id, user_name, 0, status);
	$("#chatbox_"+user_id+" .chatboxtextarea").focus();	
}

/**
 * Creates a div 
 */
function createChatBox(user_id, chatboxtitle, minimizeChatBox, online) {
	if ($("#chatbox_"+user_id).length > 0) {
		if ($("#chatbox_"+user_id).css('display') == 'none') {
			$("#chatbox_"+user_id).css('display','block');
			restructureChatBoxes();
		}
		$("#chatbox_"+user_id+" .chatboxtextarea").focus();
		return;
	}	

	user_is_online = return_online_user(user_id, online);

	$("<div />" ).attr("id","chatbox_"+user_id)
	.addClass("chatbox")
	.html('<div class="chatboxhead">\n\
			'+user_is_online+'	\n\
			<div class="chatboxtitle">'+chatboxtitle+'</div>\n\
			<div class="chatboxoptions">\n\
				<a class="togglelink" rel="'+user_id+'" href="javascript:void(0)" > _ </a>&nbsp;\n\
				<a class="closelink" rel="'+user_id+'" href="javascript:void(0)">X</a></div>\n\
				<br clear="all"/></div>\n\
			<div class="chatboxcontent"></div>\n\
			<div class="chatboxinput"><textarea class="chatboxtextarea" onkeydown="javascript:return checkChatBoxInputKey(event,this,\''+user_id+'\');"></textarea></div>')
	.appendTo($( "body" ));
			   
	$("#chatbox_"+user_id).css('bottom', '0px');
	
	chatBoxeslength = 0;

	for (x in chatBoxes) {
		if ($("#chatbox_"+chatBoxes[x]).css('display') != 'none') {
			chatBoxeslength++;
		}
	}

	if (chatBoxeslength == 0) {
		$("#chatbox_"+user_id).css('right', '245px');
	} else {
		width = (chatBoxeslength)*(225+7)+20 +225;
		$("#chatbox_"+user_id).css('right', width+'px');
	}
	
	chatBoxes.push(user_id);

	if (minimizeChatBox == 1) {
		minimizedChatBoxes = new Array();

		if ($.cookie('chatbox_minimized')) {
			minimizedChatBoxes = $.cookie('chatbox_minimized').split(/\|/);
		}
		minimize = 0;
		for (j=0;j<minimizedChatBoxes.length;j++) {
			if (minimizedChatBoxes[j] == user_id) {
				minimize = 1;
			}
		}

		if (minimize == 1) {
			$('#chatbox_'+user_id+' .chatboxcontent').css('display','none');
			$('#chatbox_'+user_id+' .chatboxinput').css('display','none');
		}
	}

	chatboxFocus[user_id] = false;

	$("#chatbox_"+user_id+" .chatboxtextarea").blur(function(){
		chatboxFocus[user_id] = false;
		$("#chatbox_"+user_id+" .chatboxtextarea").removeClass('chatboxtextareaselected');
	}).focus(function(){
		chatboxFocus[user_id] = true;
		newMessages[user_id] = false;
		$('#chatbox_'+user_id+' .chatboxhead').removeClass('chatboxblink');
		$("#chatbox_"+user_id+" .chatboxtextarea").addClass('chatboxtextareaselected');
	});

	$("#chatbox_"+user_id).click(function() {
		if ($('#chatbox_'+user_id+' .chatboxcontent').css('display') != 'none') {
			$("#chatbox_"+user_id+" .chatboxtextarea").focus();
		}
	});
	$("#chatbox_"+user_id).show();
}

/**
 * Creates the div user status (green/gray button next to the user name) 
 * @param int       user id
 * @param int       status  1 or 0
 */
function return_online_user(user_id, status) {
	var div_wrapper = $("<div />" );
	var new_div = $("<div />" );
	
	new_div.attr("id","online_"+user_id);
	new_div.attr("class","user_status");
	
	if (status == '1' || status == 1) {
		new_div.html(online_button);
	} else {
		new_div.html(offline_button);
	}
	div_wrapper.append(new_div);
	return div_wrapper.html();	
}

/**
 * Updates the user status (green/gray button next to the user name) 
 */
function update_online_user(user_id, status) {
	if ($("#online_" +user_id).length > 0) {
		if (status == 1) {
			$("#online_" +user_id).html(online_button);
		} else {
			$("#online_" +user_id).html(offline_button);
		}
	}
}


function toggleChatBoxGrowth(user_id) {		
	if ($('#chatbox_'+user_id+' .chatboxcontent').css('display') == 'none') {
		
		var minimizedChatBoxes = new Array();
		
		if ($.cookie('chatbox_minimized')) {
			minimizedChatBoxes = $.cookie('chatbox_minimized').split(/\|/);			
		}

		var newCookie = '';

		for (i=0;i<minimizedChatBoxes.length;i++) {
			if (minimizedChatBoxes[i] != user_id) {
				newCookie += user_id+'|';
			}
		}

		newCookie = newCookie.slice(0, -1);

		$.cookie('chatbox_minimized', newCookie);
		$('#chatbox_'+user_id+' .chatboxcontent').css('display','block');
		$('#chatbox_'+user_id+' .chatboxinput').css('display','block');
		$("#chatbox_"+user_id+" .chatboxcontent").scrollTop($("#chatbox_"+user_id+" .chatboxcontent")[0].scrollHeight);
	} else {		
		var newCookie = user_id;
		if ($.cookie('chatbox_minimized')) {
			newCookie += '|'+$.cookie('chatbox_minimized');
		}
		$.cookie('chatbox_minimized',newCookie);
		$('#chatbox_'+user_id+' .chatboxcontent').css('display','none');
		$('#chatbox_'+user_id+' .chatboxinput').css('display','none');
	}	
}

function checkChatBoxInputKey(event, chatboxtextarea, user_id) {
	if(event.keyCode == 13 && event.shiftKey == 0)  {
		message = $(chatboxtextarea).val();
		message = message.replace(/^\s+|\s+$/g,"");

		$(chatboxtextarea).val('');
		$(chatboxtextarea).focus();
		$(chatboxtextarea).css('height','44px');
		
		if (message != '') {
			$.post(ajax_url + "?action=sendchat", {to: user_id, message: message} , function(data) {
				message = message.replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/\"/g,"&quot;");
				
				$("#chatbox_"+user_id+" .chatboxcontent").append('<div class="chatboxmessage">\n\
				<span class="chatboxmessagefrom">'+username+':&nbsp;&nbsp;</span>\n\
				<span class="chatboxmessagecontent">'+message+'</span></div>');
				
				$("#chatbox_"+user_id+" .chatboxcontent").scrollTop($("#chatbox_"+user_id+" .chatboxcontent")[0].scrollHeight);

			});
		}
		chatHeartbeatTime = minChatHeartbeat;
		chatHeartbeatCount = 1;

		return false;
	}

	var adjustedHeight = chatboxtextarea.clientHeight;
	var maxHeight = 94;

	if (maxHeight > adjustedHeight) {
		adjustedHeight = Math.max(chatboxtextarea.scrollHeight, adjustedHeight);
		if (maxHeight)
			adjustedHeight = Math.min(maxHeight, adjustedHeight);
		if (adjustedHeight > chatboxtextarea.clientHeight)
			$(chatboxtextarea).css('height',adjustedHeight+8 +'px');
	} else {
		$(chatboxtextarea).css('overflow','auto');
	}	 
}


/**
 * Cookie plugin
 *
 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */

jQuery.cookie = function(name, value, options) {
    if (typeof value != 'undefined') { // name and value given, set cookie
        options = options || {};
        if (value === null) {
            value = '';
            options.expires = -1;
        }
        var expires = '';
        if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
            var date;
            if (typeof options.expires == 'number') {
                date = new Date();
                date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
            } else {
                date = options.expires;
            }
            expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
        }
        // CAUTION: Needed to parenthesize options.path and options.domain
        // in the following expressions, otherwise they evaluate to undefined
        // in the packed version for some reason...
        var path = options.path ? '; path=' + (options.path) : '';
        var domain = options.domain ? '; domain=' + (options.domain) : '';
        var secure = options.secure ? '; secure' : '';
        document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
    } else { // only name given, get cookie
        var cookieValue = null;
        if (document.cookie && document.cookie != '') {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var cookie = jQuery.trim(cookies[i]);
                // Does this cookie string begin with the name we want?
                if (cookie.substring(0, name.length + 1) == (name + '=')) {
                    cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                    break;
                }
            }
        }
        return cookieValue;
    }
};
