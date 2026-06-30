/**
 * Created by 冬明 on 2019/8/11.
 */
import React,{Component} from 'react'
import {BackHandler} from 'react-native';
import Http from "../utils/Http";
export default class BaseComponent extends Component{
    constructor(props){
        super(props);
        // BackHandler.addEventListener('hardwareBackPress',()=>{
        //     console.log('BaseComponent');
        //     this.props.navigation.goBack();
        //     return true;
        // } );
        this.checkLogin();
    }

    checkLogin(){
        console.log('BaseComponent --1');
        Http.post('api/checkLogin',{},'json').then((res)=>{
            console.log('BaseComponent', res);
        }).catch((e) => {
            if(e.status == 401){
                this.props.navigation.reset({
                    index: 0,
                    routes: [{name: 'Login'}]
                });
            }
        });
    }
}
// module.exports = BaseComponent;