
function accountTemplate() { }
accountTemplate._path = '/dwr';

accountTemplate.isHasMobile = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'isHasMobile', p0, callback);
}

accountTemplate.compareAccountMoney = function(p0, p1, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'compareAccountMoney', p0, p1, callback);
}

accountTemplate.ifAccountIsEffect = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'ifAccountIsEffect', p0, callback);
}

accountTemplate.accountMoney = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'accountMoney', p0, callback);
}

accountTemplate.compareAccountPickUpPassword = function(p0, p1, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'compareAccountPickUpPassword', p0, p1, callback);
}

accountTemplate.getAccountNameByName = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'getAccountNameByName', p0, callback);
}

accountTemplate.validateAccount = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'validateAccount', p0, callback);
}

accountTemplate.b2cavaiable = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'b2cavaiable', p0, callback);
}

accountTemplate.ifExistAccount = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'ifExistAccount', p0, callback);
}

accountTemplate.ifExistInPeerHis = function(p0, p1, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'ifExistInPeerHis', p0, p1, callback);
}

accountTemplate.getNameByEmail = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'getNameByEmail', p0, callback);
}

accountTemplate.ifHasEmail = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'ifHasEmail', p0, callback);
}

accountTemplate.isEmail = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'isEmail', p0, callback);
}

accountTemplate.isMobile = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'isMobile', p0, callback);
}

accountTemplate.isEmpty = function(p0, callback) {
    DWREngine._execute(accountTemplate._path, 'accountTemplate', 'isEmpty', p0, callback);
}
