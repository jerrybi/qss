/**
 * Created by 冬明 on 2019/7/18.
 */
import LocalizedStrings from 'react-native-localization'
import StorageUtil from '../utils/StorageUtil'
import en from './en'
// var I18n = require('i18n');

let strings = new LocalizedStrings({
    "en-US":en
});

// I18n.defaultLocale  = 'en';
// I18n.fallbacks = true;
// I18n.translations = {
//     en,
//     zh,
//     be
// };

// I18n.localeLanguage = () => {

//     StorageUtil.get('localLanguage',(error,object)=>{
//         if (!error && object) {
//             let res = object.res;
//             I18n.locale = res;
//         } else {
//             I18n.locale = RNLocalize.getLocales()[0].languageCode;
//         }
//     });
//     return I18n.locale;

// };


// export { I18n };
export {strings};
