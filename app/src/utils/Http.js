import axios from 'axios';
import Qs from 'querystring';
import {httpUrl} from '../common/httpBaseConfig.js';
import Toast from 'react-native-simple-toast';
import {useNavigation} from '@react-navigation/native';
import StorageUtil from './StorageUtil';
const JSON_TYPE = 'json';

const JSON_CONFIG = {
    transformRequest: [function(data) {
        console.log('json config')
        let standardJson = decodeURI(encodeURI(JSON.stringify(data)));
        return standardJson;
    }],
    headers: { 'Content-Type': 'application/json' }
};

const ajax = axios.create({
    baseURL: httpUrl,
    transformRequest: [function(data) {
        console.log('urlencoded')
        data = Qs.stringify(data);
        return data;
    }],
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    timeout: 180000
});

ajax.interceptors.response.use(
    function(response) {
        console.log('response')
        console.log(response)
        if (response.data.status === 401) {
            StorageUtil.clear();
            // const navigation = useNavigation();
            // navigation.popToTop();
            // navigation.navigate('Login')
            // response.data.message = 'Login info expired, please login again!';
            return response.data;
        }
        return response.data;
    },
    function(error) {
        console.log('error')
        console.log(error)
        // if (error.response.data.status === 401) {
        //     const navigation = useNavigation();
        //     // navigation.popToTop();
        //     navigation.navigate('Login')
        // }
        return Promise.reject(error.response.data);
    }
);

ajax.interceptors.request.use(
    async function (config) {
        // new Promise((resolve, reject) => {
        //     StorageUtil.get('token').then(response => {
        //         console.log('[ajax.interceptors.request]token:'+response);
        //         config.headers['token'] = response;
        //         resolve(config);
        //     }, reject);
        // });
        let token = await StorageUtil.get('token');
        console.log('request token:', token);
        if (token) {
            config.headers['token'] = token;
        }
        return config;
    },
    function(error) {
        console.log('request error')
        console.log(error)
        return Promise.reject(error);
    }
);

/**
 * GET请求方法封
 * @param {*} url request url
 * @param {*} params param
 * @param {*} dataType
 */
export function get(url, params = {}, dataType) {
    return new Promise((resolve, reject) => {
        ajax.get(url, { params: params },
            dataType !== JSON_TYPE ? '' : JSON_CONFIG
        ).then(res => {
            if (res.status === 200) {
                resolve(res);
            } else {
                reject(res)
                Toast.showWithGravity(res.msg, 2,Toast.CENTER)
            }
        }).catch(error => {
            reject(error)
        })
    })
}

/**
 * POST请求方法封装
 * @param {*} url request url
 * @param {*} data
 * @param {*} dataType
 */
export function post(url, data = {}, dataType) {
    return new Promise((resolve, reject) => {
        ajax.post(
            url, {...data },
            dataType !== JSON_TYPE ? '' : JSON_CONFIG
        ).then(res => {
            console.log('post -1-');
            console.log(res);
            if (res.status === 200) {
                resolve(res);
            } else {
                reject(res);
                console.log(res.message);
                Toast.showWithGravity(res.message, 2,Toast.CENTER)
            }
        }).catch(error => {
            console.log('error --1--')
            console.log(error)
            if(error && error.length > 0){
                Toast.showWithGravity(error[0], 2,Toast.CENTER)
            }
            reject(error);
        });
    });
}

module.exports = {
    get: get,
    post: post
};
