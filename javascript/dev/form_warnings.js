var forms = array();
var updatedForms = array();

function setWarningOnNavigate(){
    forms = document.getElementsByTagName("form");
    for(var form in forms){
        var formName = form.name;
        updatedForms[formName] = false;
        var fields = form.querySelectorAll("input,textarea");
        for(var field in fields){
            field.addEventListener('input', function(){
                updatedForms[formName] = true;
            });
        }
        form.addEventListener('submit', function(){
            if (updatedForms[formName]){
                return confirm('You are going to lose your changes. Do you wish to proceed?');
            }
            return true;
        });
    }
}
