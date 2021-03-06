function validateRegistration()
{
    if ( ($("#loginname").val() === "") ||
         ($("#loginname").val() == L("Username")) )
    {
        notify( L("EnterValidUsername") );
        return false;
    }

    if ( ($("#loginpass").val() === "") ||
         ($("#loginpass").val() == L("Password")) )
    {
        notify( L("EnterNonEmptyPassword") );
        return false;
    }

    if ( $("#loginpass").val() != $("#loginpass_repeat").val() )
    {
        notify( L("PasswordsNotMatch") );
        return false;
    }

    var Parameters = {
        name : $("#loginname").val(),
        pass : $("#loginpass").val()
    };

    asyncQuery( "user_create", Parameters, function(aXHR) {
        if (aXHR.publicmode)
            notify( L("RegistrationDone") );
        else
            notify( L("RegistrationDone") + "<br/>" + L("ContactAdminToUnlock") );

        changeContext("login");
        generateLogin();
    });
}

// -----------------------------------------------------------------------------

function switchRegisterPassField()
{
    $("#loginpass").after("<input id=\"loginpass\" type=\"password\" class=\"textactive\" name=\"pass\"/>");
    $("#loginpass:first").detach();

    $("#loginpass").focus();
    $("#loginpass").blur( function() {
        if ( $(this).val() === "" )
        {
            $(this).unbind("blur"); // avoid  additional call once entered
            $(this).detach();
            $("#loginpass_repeat").before("<input id=\"loginpass\" type=\"text\" class=\"text\" value=\"" + L("Password") + "\">");
            $("#loginpass").focus( switchRegisterPassField );
        }
    });

    $("#loginpass").keyup( function() {
        var strength = getPasswordStrength( $("#loginpass").val() );
        var width = parseInt(strength.quality*100, 10);

        $("#strength").css("background-color", strength.color).css("width",width+"%");
    });
}

// -----------------------------------------------------------------------------

function switchRegisterPassRepeatField()
{
    $("#loginpass_repeat").after("<input id=\"loginpass_repeat\" type=\"password\" class=\"textactive\" name=\"pass_repeat\"/>");
    $("#loginpass_repeat:first").detach();

    $("#loginpass_repeat").focus();
    $("#loginpass_repeat").blur( function() {
        if ( $(this).val() === "" )
        {
            $(this).unbind("blur"); // avoid  additional call once entered
            $(this).detach();
            $("#loginpass").after("<input id=\"loginpass_repeat\" type=\"text\" class=\"text\" value=\"" + L("RepeatPassword") + "\">");
            $("#loginpass_repeat").focus( switchRegisterPassRepeatField );
        }
    });
}

// -----------------------------------------------------------------------------

function generateRegistration()
{
    var HTMLString = "";

    HTMLString += "<div id=\"loginform\" style=\"margin-top:-80px\">";
    HTMLString += "<input type=\"hidden\" name=\"register\"/>";
    HTMLString += "<div>";
    HTMLString += "<input id=\"loginname\" type=\"text\" class=\"text\" value=\"" + L("Username") + "\"/>";
    HTMLString += "</div>";
    HTMLString += "<div id=\"strprogress\"><span class=\"pglabel\">"+L("PassStrength")+"</span><span id=\"strength\" class=\"progress\"></span></div>";
    HTMLString += "<div>";
    HTMLString += "<input id=\"loginpass\" type=\"text\" class=\"text\" value=\"" + L("Password") + "\"/>";
    HTMLString += "</div>";
    HTMLString += "<div>";
    HTMLString += "<input id=\"loginpass_repeat\" type=\"text\" class=\"text\" value=\"" + L("RepeatPassword") + "\"/>";
    HTMLString += "</div>";
    HTMLString += "<button id=\"doregister\" onclick=\"validateRegistration()\" style=\"margin-left: 5px\" class=\"button_register\">" + L("Register") + "</button>";
    HTMLString += "</div>";

    $("#body").empty().append(HTMLString);

    $("#loginname").focus( function() {
        $("#loginname").removeClass("text").addClass("textactive");

        if ( $("#loginname").val() == L("Username") )
            $("#loginname").val("");
    });

    $("#loginname").blur( function() {
        if ( $("#loginname").val() === "" )
        {
            $("#loginname").removeClass("textactive").addClass("text");
            $("#loginname").val(L("Username"));
        }
    });

    $("#loginpass").focus( switchRegisterPassField );
    $("#loginpass_repeat").focus( switchRegisterPassRepeatField );

    $("#doregister").button();
}