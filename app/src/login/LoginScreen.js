import React,{Component} from 'react';
import Toast from 'react-native-simple-toast';
import CountEmitter from '../event/CountEmitter';
import StorageUtil from '../utils/StorageUtil';
import LoadingView from '../views/LoadingView';
import Utils from '../utils/Utils';
import {strings} from '../language/locale';
import Http from '../utils/Http';
import Aes from '../utils/Aes';
import CryptoJS from 'crypto-js';

import {Dimensions, Image, StyleSheet, Text, TextInput, TouchableOpacity, View} from 'react-native';
import {useDispatch} from "react-redux";
import {actionCreator} from "../../store/actions";

const {width} = Dimensions.get('window');

export default class LoginScreen extends Component {
    constructor(props) {
        super(props);
        this.timer = null;
        this.interval = 0;
        this.state = {
            inputUsername: '',
            username: '',
            password: '',
            showProgress: false,
            avatar: '',
            countryName:'中国大陆',
            phoneCountryCode:'+86',
            inputVfy:'',
            vfyTime:strings.getCode,
            loginMode:2,
            warnMsg:'',
            vfyDisable:false
        };
    }
    
    componentWillUnmount(){
        if(this.timer != null){
            clearInterval(this.timer);
            this.timer = null;
        }
        CountEmitter.removeListener("Language",()=>{});
    }
    componentDidMount() {
        let that = this;
        StorageUtil.get('token').then(token => {
            console.log('[componentDidMount]'+token)
            console.log(token);
            if(token && token.length > 0){
                that.props.navigation.replace('Main');
                // const dispatch = useDispatch();
                // // dispatch(actionCreator(true))
                // dispatch({
                //     type: 'hasLogin',
                //     payload: true
                // })
            }
        }).catch(e => {});
    }

    shouldComponentUpdate(nextProps, nextState, nextContext) {
        console.log('shouldComponentUpdate')
        console.log(nextProps)
        return true;
    }

    render() {
        return (
            <View style={styles.container}>
                <View style={styles.content}>
                    {
                        <View style={styles.pwdView}>
                            <Image source={require('../../images/logo.png')}
                                   style={styles.logo}/>
                            <View style={styles.pwdContainer}>
                                <Image source={require('../../images/username.png')}
                                       style={styles.iconUserName}/>
                                <TextInput
                                    onChangeText={(text) => {
                                        this.setState({inputUsername: text})
                                    }}
                                    style={styles.textInput}
                                    underlineColorAndroid="transparent"
                                    placeholder={strings.usernameInputHint}
                                    placeholderTextColor={'#999999'}
                                    defaultValue={this.state.inputUsername}
                                    keyboardType='default'/>
                            </View>
                            <View style={styles.passwordContainer}>
                                <Image source={require('../../images/password.png')}
                                       style={styles.iconPwd}/>
                                <TextInput onChangeText={(text) => {
                                    this.setState({inputPass: text})
                                }} style={styles.textPass} underlineColorAndroid="transparent"
                                           placeholder={strings.passInputHint}
                                           placeholderTextColor={'#999999'}
                                           secureTextEntry={true}
                                           defaultValue={this.state.inputPass}
                                />
                            </View>

                        </View>
                    }
                    {
                        this.state.showProgress ? (
                            <LoadingView cancel={() => this.setState({showProgress: false})}/>
                        ) : (null)
                    }
                    <TouchableOpacity activeOpacity={0.6} onPress={() => this.login()}>
                        <View style={styles.loginBtn}>
                            <Text style={{color: '#FFFFFF', fontSize: 16}}>{strings.login}</Text>
                        </View>
                    </TouchableOpacity>
                </View>
            </View>
        );
    }

    login() {
        console.log('login')
        this.loginByPassword();
    }

    loginByPassword(){
        console.log('loginByPassword')
        let username = this.state.inputUsername;
        let password = this.state.inputPass;
        if (Utils.isEmpty(username) || Utils.isEmpty(password)) {
            Toast.showWithGravity(strings.Usernameorpassword, 2,Toast.CENTER);//用户名或密码不能为空
            return;
        }
        let md5Pwd = CryptoJS.MD5(password).toString();
        let publicKey = Aes.encrypt(username, md5Pwd, 'qsxxqsxxqsxxqsxx');
        let jsonData = {
            username:username,
            public_key:publicKey
        };
        this.setState({showProgress: true});
        console.log(jsonData)
        Http.post('api/login',jsonData,'json').then((json) => {
            console.log(json)
            this.setState({showProgress: false});
            //清除保存的账号密码
            this.setState({inputUsername: '', inputPass: '', username: '', password: ''});
            if (!Utils.isEmpty(json)) {
                if (json.status == '200') {
                    console.log(json);
                    // 登录服务器成功，再登录NIM的服务器
                    let token = json.data.token;
                    let user = json.data.user;
                    console.log('token:'+token);
                    console.log(user)
                    StorageUtil.set('token', token);
                    StorageUtil.set('user',JSON.stringify(user))
                    StorageUtil.set('hasLogin',"1")
                    // const dispatch = useDispatch();
                    // // dispatch(actionCreator(true))
                    // dispatch({
                    //     type: 'hasLogin',
                    //     payload: true
                    // })

                    //进入主界面
                    this.props.navigation.replace('Main');
                    console.log('--end--');
                } else {
                    Toast.showWithGravity(strings.Loginfailed, 2,Toast.CENTER);//登录失败
                }
            } else {
                Toast.showWithGravity(strings.Loginfailed, 2,Toast.CENTER);//登录失败
            }
        }).catch((e) => {
            this.setState({showProgress: false});
            console.log(e);
        });
    }
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        flexDirection: 'column',
        backgroundColor: '#ffffff'
    },
    content: {
        flex: 1,
        flexDirection: 'column',
        alignItems: 'center',
    },
    pwdView: {
        flex: 0,
        flexDirection: 'column',
        alignItems: 'center',
        marginTop: 50,
    },
    textInput: {
        flex: 1,
        paddingLeft:9,
        color: '#000000'
    },
    usernameText: {
        marginTop: 10,
        fontSize: 16,
        textAlign: 'center'
    },
    pwdContainer: {
        width:300,
        height:48,
        backgroundColor:"rgba(194,197,194,0.2)",
        flexDirection: 'row',
        alignItems: 'center',
        marginTop:10,
        borderRadius:5
    },
    phoneCountryCode:{
        marginLeft:13,
        marginRight:13
    },
    divider:{
        height:48,
        width:1,
        backgroundColor:"rgba(153,153,153,0.2)"
    },
    vfyContainer: {
        width:300,
        height:48,
        flexDirection: 'row',
        justifyContent:"space-between",
        alignItems: 'center',
        marginTop:10
    },
    vfyInputContainer:{
        flexDirection:"row",
        alignItems:"center",
        backgroundColor:"rgba(194,197,194,0.2)",
        borderRadius:5,
        width:180
    },
    vfyMask:{
        width: 18,
        height: 20,
        marginLeft:12
    },
    textVfy:{
        flex: 1,
        paddingLeft:5
    },
    vfyTime:{
        borderWidth:1,
        borderColor:"#0BA50B",
        borderRadius:5,
        width:100,
        height:48,
        lineHeight:48,
        textAlign:"center",
        color:"#999999"
    },
    loginBtn: {
        width: 200,
        marginLeft: 20,
        marginRight: 20,
        marginTop: 20,
        height: 48,
        borderRadius: 5,
        backgroundColor: '#283560',
        justifyContent: 'center',
        alignItems: 'center',
        color:"#FFFFFF",
        fontSize:18,
        fontWeight:"500"
    },
    changeAccount: {
        fontSize: 16,
        color: '#00BC0C',
        textAlign: 'center',
        marginBottom: 20
    },
    registView:{
        alignSelf:"flex-end"
    },
    registText:{
        color:"#0BA50B",
        fontSize:15,
        marginRight:20,
        marginTop:10,
        fontWeight:"500"
    },
    countryContainer:{
        flexDirection:"row",
        justifyContent:"space-between",
        alignItems:"center",
        width:300,
        height:48,
        backgroundColor:"rgba(194,197,194,0.2)",
        borderRadius:5
    },
    countryTitle:{
        width:80,
        height:48,
        lineHeight:48,
        alignSelf:"flex-start",
        color:"#666666",
        fontSize:15,
        marginLeft:10
    },
    countryNameContainer:{
        height:48,
        alignSelf:"flex-end",
        alignItems:"center",
        flexDirection:"row"
    },
    countryName:{
        height:48,
        lineHeight:48,
        marginRight:10,
        color:"#333333"
    },
    countryArrow:{
        marginRight:10,
        color:"#999999"
    },
    passwordLogin:{
        width:width,
        color:"#0BA50B",
        fontSize:13,
        marginBottom:45,
        textAlign:"center",
        fontWeight:"400"
    },
    vfyLogin:{
        width:width,
        color:"#0BA50B",
        fontSize:13,
        marginBottom:45,
        textAlign:"center",
        fontWeight:"400"
    },
    passwordContainer:{
        flexDirection:"row",
        alignItems:"center",
        width:300,
        height:48,
        backgroundColor:"rgba(194,197,194,0.2)",
        borderRadius:5,
        marginTop:10
    },
    textPass:{
        flex: 1,
        paddingLeft:5,
        color: '#000000'
    },
    forgetPwd:{
        color:'#0BA50B',
        marginTop:10,
        width:300,
        textAlign:"right"
    },
    logo:{
        width: 100,
        height: 100,
        marginBottom: 40,
        borderRadius:20
    },
    iconUserName:{
        width: 16,
        height: 16,
        marginLeft: 12
    },
    iconPwd:{
        width: 16,
        height: 20,
        marginLeft:12
    }
});
