//** 加密 **
//var ciphertext = CryptoJS.AES.encrypt(message, key, cfg);
//params: 注意参数key为WordArray对象
//return: 密码对象 或者 密码对象Base64字符串
function aesEncrypt(message,key,iv){
	var ciphertext = CryptoJS.AES.encrypt(message, CryptoJS.enc.Utf8.parse(key), {	
		iv: CryptoJS.enc.Utf8.parse(iv),
		mode: CryptoJS.mode.CBC,
		padding:CryptoJS.pad.Pkcs7 
 	});
	//return ciphertext;//密码对象(Obejct类型，非WordArray类型)，Base64编码。
	return  ciphertext.toString();//密码对象的Base64字符串
}

//** 解密 **
//var plaintext  = CryptoJS.AES.decrypt(ciphertext, key, cfg);
//params: 注意参数ciphertext 必须为 Base64编码的对象或者字符串。
function aesDecrypt(ciphertext,key,iv){
	var decrypted = CryptoJS.AES.decrypt(ciphertext,CryptoJS.enc.Utf8.parse(key),{ 
        iv: CryptoJS.enc.Hex.parse(iv),
        mode: CryptoJS.mode.CBC,
        padding:CryptoJS.pad.Pkcs7 
    });
	return decrypted.toString(CryptoJS.enc.Utf8);//WordArray对象转utf8字符串
}