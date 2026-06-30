import React from 'react';
import StorageUtil from '../utils/StorageUtil';
import BaseComponent from './BaseComponent';

import {
    Animated, Button,
    Dimensions,
    Image, NativeEventEmitter,
    StatusBar,
    StyleSheet,
    Text, TextInput,
    TouchableOpacity,
    View
} from 'react-native';
import QRCodeScanner from "react-native-qrcode-scanner";
import {RNCamera} from "react-native-camera";
import Global from "../utils/Global";
import {parse as VCardParse} from "../utils/VCard";
import {addContact, getContact, updateContacts} from "../../db/contacts";
import {connectToDatabase} from "../../db/db";
import TimeUtil from "../utils/TimeUtil";
import singleCountEmitter from "../event/CountEmitter";
import {DismissKeyboardView} from "../views/DismissKeyboardView";
import CommonTitleBar from "../views/CommonTitleBar";

const {width, height} = Dimensions.get('window');

export default class ScanScreen extends BaseComponent {
    constructor(props) {
        super(props);
        props.navigation.setOptions({
            headerTitle: 'Scan'
        });
        this.isAutoLogin = false;
        this.timer = null;
        this.scanner = null;
        this.viewFocused = null;
        this.state = {
            fadeAnim: new Animated.Value(0),
            hasLogin: false,
            scan:true,
            scanResult:false,
            contact:{},
        };
    }

    render() {
        const {scan, scanResult, contact, viewFocused} = this.state;
        return (
            <View style={styles.container}>
                <View>
                    <StatusBar backgroundColor="#000000"/>
                </View>
                <View style={styles.content}>
                    {
                        scan && viewFocused ?
                        <QRCodeScanner
                            reactivate={true}
                            showMarker={true}
                            ref={(node)=>{this.scanner = node}}
                            onRead={this.onSuccess}
                            flashMode={RNCamera.Constants.FlashMode.off}
                            topContent={
                                <Text style={styles.centerText}>Please scan the QR code</Text>
                            }
                            bottomContent={<></>}
                        />
                        : <></>
                    }
                    {
                        scanResult ? (
                            <DismissKeyboardView style={styles.screen}>
                            <View style={styles.scanResult}>
                                {contact.type == 'vcard' ?
                                <View style={styles.contact}>
                                    <Text style={styles.contactInfo}>{contact.serialNumber}</Text>
                                    <Text style={styles.contactInfo}>{contact.firstName} {contact.lastName}</Text>
                                    <Text style={styles.contactInfo}>{contact.organization}</Text>
                                    <Text style={styles.contactInfo}>{contact.title}</Text>
                                    <Text style={styles.contactInfo}>{contact.telephone}</Text>
                                    <Text style={styles.contactInfo}>{contact.email}</Text>
                                </View>:
                                <View style={styles.contact}>
                                    <Text style={styles.contactInfo}>{contact.serialNumber}</Text>
                                </View>
                                }
                                <View style={styles.remarkContainer}>
                                    <TouchableOpacity style={[styles.remarkBtn,styles.cool,contact.flag === 'Cool'?styles.flagActive:null]}
                                                      onPress={()=>{this.onPressFlag('Cool')}}>
                                        <Text style={styles.flagText}>Cool</Text>
                                    </TouchableOpacity>
                                    <TouchableOpacity style={[styles.remarkBtn,styles.warm,contact.flag === 'Warm'?styles.flagActive:null]}
                                                      onPress={()=>{this.onPressFlag('Warm')}}>
                                        <Text style={styles.flagText}>Warm</Text>
                                    </TouchableOpacity>
                                    <TouchableOpacity style={[styles.remarkBtn,styles.hot,contact.flag === 'Hot'?styles.flagActive:null]}
                                                      onPress={()=>{this.onPressFlag('Hot')}}>
                                        <Text style={styles.flagText}>Hot</Text>
                                    </TouchableOpacity>
                                </View>
                                <View style={styles.remarkText}>
                                    <Text style={styles.remarkTitle}>Remarks</Text>
                                        <TextInput
                                            style={styles.remark}
                                            onChangeText={text => this.onChangeRemark(text)}
                                            multiline={true}
                                            numberOfLines={5}
                                            placeholder="Please enter remark"
                                            placeholderTextColor={'#aaa'}
                                            value={contact.remark}
                                        />
                                </View>
                                <View style={styles.bottom}>
                                    <TouchableOpacity
                                        style={[styles.btn,styles.skip]}
                                        onPress={() => {this.skip()}}
                                    >
                                        <Text style={[styles.colorWhite,styles.textSize]}>Cancel</Text>
                                    </TouchableOpacity>
                                    <TouchableOpacity
                                        style={[styles.btn,styles.submit]}
                                        onPress={() => {this.submit()}}
                                    >
                                        <Text style={styles.textSize}>Submit</Text>
                                    </TouchableOpacity>
                                </View>
                            </View>
                            </DismissKeyboardView>
                        ) : (<></>)
                    }
                </View>
            </View>
        );
    }

    onSuccess = (e) => {
        console.log('QR code scanned!', e)
        let data = e.data;
        console.log(data)
        let result = VCardParse(data);
        console.log(result);
        let contact = {}
        if (result.length > 0) {
            for(let i in result[0]){
                console.log(i)
                console.log(result[0][i])
                let item = result[0][i]
                if(i === 'name'){
                    contact.firstName = item?.name
                    contact.lastName = item?.surname
                }else if(i === 'displayName'){
                    contact.fullName = item
                }else if(i === 'organization'){
                    contact.organization = item
                }else if(i === 'title'){
                    contact.title = item;
                }else if(i === 'telephone'){
                    let arr = []
                    for(let x in item){
                        arr.push(item[x].value)
                    }
                    contact.telephone = arr.join('|')
                }else if(i === 'email'){
                    let arr = []
                    for(let x in item){
                        arr.push(item[x].value)
                    }
                    contact.email = arr.join('|')
                }else if(i === 'UID'){
                    contact.serialNumber = item;
                }
            }
            contact.type = 'vcard'
        } else {
            contact.type = 'uid';
            contact.serialNumber = data;
        }
        // check if already stored this serial number at the same day
        connectToDatabase().then((db) => {
            console.log(db)
            const visitDate = TimeUtil.currentFormatDate()
            getContact(db, contact.serialNumber, visitDate).then((res)=>{
                console.log('db contact', res)
                contact.flag = res?.flag;
                contact.remark = res?.remark;
                console.log(contact)
                this.setState({
                    scan:false,
                    scanResult:true,
                    contact:contact
                })
                this.props.navigation.setOptions({
                    headerTitle: 'Scanned Data'
                });
            })
        });
    }

    onChangeRemark(text){
        let {contact} = this.state;
        contact.remark = text
        this.setState({
            contact:contact
        })
    }

    onPressFlag(flag){
        let {contact} = this.state;
        contact.flag = flag
        this.setState({
            contact:contact
        })
    }

    skip(){
        this.setState({
            scan:true,
            scanResult:false
        })
        this.props.navigation.setOptions({
            headerTitle: 'Scan'
        });
    }

    submit(){
        connectToDatabase().then(async (db) => {
            console.log('submit-1', db)
            let {contact} = this.state;
            contact.visitTime = TimeUtil.currentFormatTime()
            contact.visitDate = TimeUtil.currentFormatDate()
            if (contact.flag !== 'Cool' && contact.flag !== 'Warm' && contact.flag !== 'Hot') {
                contact.flag = 'Neutral';
            }
            const res = await getContact(db, contact.serialNumber, contact.visitDate);
            console.log('submit-2', res)
            if (res?.serialNumber) {
                contact.id = res?.id;
                await updateContacts(db, contact);
            } else {
                await addContact(db, contact);
            }
            this.setState({
                scan: true,
                scanResult: false
            })
            this.props.navigation.setOptions({
                headerTitle: 'Scan'
            });
            singleCountEmitter.emit('contacts_update')
        });
    }

    componentWillUnmount() {
        if(this.timer != null){
            clearTimeout(this.timer);
            this.timer = null;
        }
    }
    componentDidMount() {
        this.props.navigation.addListener('focus', () => {
            this.setState({viewFocused: true});
        });

        this.props.navigation.addListener('blur', () => {
            this.setState({viewFocused: false});
        });
    }
}

const styles = StyleSheet.create({
    screen: {
        width: width,
        height: height,
        flex:1,
        flexDirection: 'column',
        justifyContent: 'center',
        alignItems: 'center'
    },
    buttonContainer: {
        position: 'absolute',
        bottom: 50,
        flex: 1,
        flexDirection: 'row',
        paddingLeft: 20,
        paddingRight: 20,
    },
    btnColumn: {
        flex: 1,
        paddingLeft: 10,
        paddingRight: 10,
        justifyContent: 'center',
        alignItems: 'center',
        borderRadius: 3,
    },
    button: {
        flex: 1,
        paddingTop: 10,
        paddingBottom: 10,
        fontSize: 16,
    },
    btnLogin: {
        backgroundColor: '#FFFFFF',
        marginRight: 15,
    },
    btnRegister: {
        backgroundColor: '#00BC0C',
        marginLeft: 15,
    },
    container: {
        flex: 1,
        flexDirection: 'column',
        justifyContent: 'center',
        alignItems: 'center'
    },
    content: {
        flex: 1,
        width: width,
        flexDirection: 'column',
        justifyContent: 'center',
        alignItems: 'center',
        backgroundColor: Global.pageBackgroundColor
    },
    centerText: {
        flex: 1,
        fontSize: 20,
        padding: 32,
        color: '#777'

    },
    scanResult:{
        width: '80%',
        height: '80%'
    },
    remarkBtn:{
        borderRadius: 8,
        width: '30%',
        height: 30,
        alignItems:'center',
        justifyContent:'center'
    },
    flagText:{
        fontSize: 18,
        fontWeight: 'bold'
    },
    cool: {
        backgroundColor: '#0098e8',
        color: '#ffffff'
    },
    warm: {
        backgroundColor: '#ffb621',
        color: '#000000'
    },
    hot: {
        backgroundColor: '#ff0000',
        color: '#ffffff'
    },
    flagActive:{
        transform:[
            {
                scaleY: 1.2
            }
        ],
        borderColor: '#18864b',
        borderWidth: 2
    },
    contact:{
        flex:1,
        flexDirection: 'column',
        justifyContent: 'center',
        alignItems: 'center'
    },
    contactInfo:{
        color:'#000',
        fontSize: 18
    },
    bottom: {
        flex: 1,
        flexDirection: 'row',
        justifyContent: 'space-around',
        alignItems: 'center'
    },
    remarkContainer: {
        flex: 1,
        flexDirection: 'row',
        justifyContent: 'space-around',
        alignItems: 'center'
    },
    remarkText:{
        flex: 1,
        flexDirection: 'column',
        justifyContent: 'center',
        alignItems: 'flex-start'
    },
    remarkTitle:{
        color:'#000'
    },
    remark: {
        width: '100%',
        height: 80,
        borderColor: '#ccc',
        borderWidth: 1,
        alignItems:'flex-start',
        textAlignVertical:'top',
        color:'#000'
    },
    btn:{
        width: '40%',
        height: 40,
        alignItems:'center',
        justifyContent:'center',
        borderRadius:8,
        color: '#ffffff',
        fontWeight: 'bold'
    },
    skip:{
        backgroundColor:'#333333'
    },
    submit:{
        backgroundColor:'#1e80ff'
    },
    colorWhite:{
        color: '#ffffff'
    },
    textSize:{
        fontSize: 18
    }
});
