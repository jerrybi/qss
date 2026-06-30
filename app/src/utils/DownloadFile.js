import RNFS from 'react-native-fs';
import {
    Platform,
} from 'react-native';

// const EXTERNAL_PATH = 'file:///data/user/0/com.xinlian.haha/cache/Camera';

/*
    uri: 下载来源
    which: 下载时重命名
    callback: 回调
*/
export default DownloadFile =(uri, which, callback)=> {
    if (!uri) return null;
    return new Promise((resolve, reject) => {
        let dirs = Platform.OS === 'ios' ? RNFS.LibraryDirectoryPath : RNFS.ExternalDirectoryPath; //外部文件，共享目录的绝对路径（仅限android）
        const downloadDest = `${dirs}/${which}`;
        const formUrl = uri;
        const options = {
            fromUrl: formUrl,
            toFile: downloadDest,
            background: true,
            begin: (res) => {
            },
            progress: (res) => {
                let pro = res.bytesWritten / res.contentLength;
                callback(pro);//下载进度
            }
    
        };
        try {
            const ret = RNFS.downloadFile(options);
            ret.promise.then(res => {
                console.log('success', res);
                console.log('file://' + downloadDest)
                resolve(res);
            }).catch(err => {
                reject(new Error(err))
            });
        } catch (e) {
            reject(new Error(e))
        } 
    })
}