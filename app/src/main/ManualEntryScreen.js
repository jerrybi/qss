import React, {Component} from "react";
import {
    Dimensions, Image, Modal,
    NativeEventEmitter,
    PixelRatio,
    processColor, ScrollView,
    StatusBar,
    StyleSheet,
    Text, TextInput, TouchableOpacity, TouchableWithoutFeedback, TouchableWithoutFeedbackComponent,
    View
} from "react-native";
import Global from "../utils/Global";
import TimeUtil from '../utils/TimeUtil'
import {connectToDatabase} from "../../db/db";
import {addContact, getContacts, getContactsNum, getReportByFlag} from "../../db/contacts";
import singleCountEmitter from "../event/CountEmitter";
import {DismissKeyboardView} from "../views/DismissKeyboardView";
import {ActionDom, ActionSheet} from "../views/ActionSheet";
import * as ImagePicker from "react-native-image-picker";
import Toast from "react-native-simple-toast";
import {strings} from "../language/locale";
import ImageViewer from "react-native-image-zoom-viewer";

const {width,height} = Dimensions.get('window');

export default class ManualEntryScreen extends Component {
    constructor(props) {
        super(props);
        this.state = {
            total:0,
            data:[],
            contact:{},
            showAction:false,
            showPictureModal:false
        };
    }

    componentDidMount() {
        console.log('[index][componentDidMount]');
        singleCountEmitter.addListener('contacts_update',event=>{

        })
    }

    componentWillUnmount() {
        console.log('[index][componentWillUnmount]');
        singleCountEmitter.removeAllListeners('contacts_update')
    }

    handleBackPress = () => {
        console.log('handleBackPress');
        console.log(this.props.navigation.state.routes);
        if(this.props.navigation.state.routes.length > 1){
            this.props.navigation.goBack();
            return true;
        }
        return false;
    }

    render() {
        const {contact,showPictureModal} = this.state;
        return (
            <ScrollView contentContainerStyle={{flexGrow:1}} showsVerticalScrollIndicator={false}
                automaticallyAdjustKeyboardInsets={true}>
                <DismissKeyboardView  style={styles.screen}>
                <View style={styles.container}>
                        <View style={[styles.item,styles.item1]}>
                            <Text style={styles.label}>First Name</Text>
                            <TextInput
                                style={styles.input}
                                onChangeText={text => this.onChangeFirstName(text)}
                                placeholder="Please enter first name"
                                placeholderTextColor={'#aaa'}
                            />
                        </View>
                        <View style={styles.item}>
                            <Text style={styles.label}>Last Name</Text>
                            <TextInput
                                style={styles.input}
                                onChangeText={text => this.onChangeLastName(text)}
                                placeholder="Please enter last name"
                                placeholderTextColor={'#aaa'}
                            />
                        </View>
                        <View style={styles.item}>
                            <Text style={styles.label}>Organisation</Text>
                            <TextInput
                                style={styles.input}
                                onChangeText={text => this.onChangeOrganisation(text)}
                                placeholder="Please enter organisation"
                                placeholderTextColor={'#aaa'}
                            />
                        </View>
                        <View style={styles.item}>
                            <Text style={styles.label}>Job Title</Text>
                            <TextInput
                                style={styles.input}
                                onChangeText={text => this.onChangeTitle(text)}
                                placeholder="Please enter job title"
                                placeholderTextColor={'#aaa'}
                            />
                        </View>
                        <View style={styles.item}>
                            <Text style={styles.label}>Email</Text>
                            <TextInput
                                style={styles.input}
                                onChangeText={text => this.onChangeEmail(text)}
                                placeholder="Please enter email"
                                placeholderTextColor={'#aaa'}
                            />
                        </View>
                        <View style={styles.item}>
                            <Text style={styles.label}>Mobile</Text>
                            <TextInput
                                style={styles.input}
                                onChangeText={text => this.onChangeMobile(text)}
                                placeholder="Please enter mobile"
                                placeholderTextColor={'#aaa'}
                            />
                        </View>
                        <View style={styles.item}>
                            <Text style={styles.label}>Card Image</Text>
                            <View style={styles.cardBg}>
                                <TouchableOpacity style={styles.cardContainer}
                                                  onPress={()=>{this.takePicture()}}>
                                    <Text style={styles.cardBtn}>Take Picture</Text>
                                </TouchableOpacity>
                                {contact.imgCard ? <TouchableWithoutFeedback onPress={()=>this.handleShowModal()}>
                                    <Image source={{uri: 'data:image/png;base64,' + contact.imgCard}}
                                                          style={styles.imgCard}/>
                                </TouchableWithoutFeedback> : null}
                            </View>
                        </View>
                        <View style={styles.remarkContainer}>
                            <TouchableOpacity style={[styles.remarkBtn,styles.cool,contact.flag === 'Cool'?styles.flagActive:null]}
                                              onPress={()=>{this.onPressFlag('Cool')}}>
                                <Text style={styles.flag}>Cool</Text>
                            </TouchableOpacity>
                            <TouchableOpacity style={[styles.remarkBtn,styles.warm,contact.flag === 'Warm'?styles.flagActive:null]}
                                              onPress={()=>{this.onPressFlag('Warm')}}>
                                <Text style={styles.flag}>Warm</Text>
                            </TouchableOpacity>
                            <TouchableOpacity style={[styles.remarkBtn,styles.hot,contact.flag === 'Hot'?styles.flagActive:null]}
                                              onPress={()=>{this.onPressFlag('Hot')}}>
                                <Text style={styles.flag}>Hot</Text>
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
                            />
                        </View>

                        <TouchableOpacity style={[styles.btn,styles.submit]}
                                          onPress={()=>{this.submitRecord()}}>
                            <Text>Submit</Text>
                        </TouchableOpacity>
                </View>
                <ActionSheet
                    showAction={this.state.showAction}
                    cancel={()=>{this.setState({showAction:false})}}
                >
                    <View>
                        <ActionDom
                            actionName={'Take Image'}
                            onPress={()=>{
                                ImagePicker.launchCamera({
                                    saveToPhotos: true,
                                    mediaType: 'photo',
                                    includeBase64: true,
                                    includeExtra: true,
                                    quality: 0.4
                                }).then(r =>{
                                    this.imageResponse(r);
                                })
                            }}
                        />
                        <ActionDom
                            actionName={'Select Image'}
                            onPress={()=>{
                                ImagePicker.launchImageLibrary({
                                    selectionLimit: 1,
                                    mediaType: 'photo',
                                    includeBase64: true,
                                    includeExtra: true,
                                    quality: 0.4
                                }).then(r =>{
                                    this.imageResponse(r);
                                })
                            }}
                        />
                    </View>
                </ActionSheet>
                <Modal visible={showPictureModal} transparent={true}>
                    <ImageViewer
                        imageUrls={[
                            {
                                url: 'data:image/png;base64,' + this.state.contact.imgCard,
                            },
                        ]}
                        onClick={() => this.handleHideModel()}
                    />
                </Modal>
                </DismissKeyboardView>
            </ScrollView>
        );
    }

    onChangeFirstName(text){
        let {contact} = this.state;
        contact.firstName = text
        this.setState({
            contact:contact
        })
    }

    onChangeLastName(text){
        let {contact} = this.state;
        contact.lastName = text
        this.setState({
            contact:contact
        })
    }

    onChangeOrganisation(text){
        let {contact} = this.state;
        contact.organization = text
        this.setState({
            contact:contact
        })
    }

    onChangeTitle(text){
        let {contact} = this.state;
        contact.title = text
        this.setState({
            contact:contact
        })
    }

    onChangeEmail(text){
        let {contact} = this.state;
        contact.email = text
        this.setState({
            contact:contact
        })
    }

    onChangeMobile(text){
        let {contact} = this.state;
        contact.telephone = text
        this.setState({
            contact:contact
        })
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

    submitRecord() {
        let that = this;
        connectToDatabase().then((db) => {
            console.log(db)
            let {contact} = this.state;
            contact.serialNumber = '';
            contact.visitTime = TimeUtil.currentFormatTime()
            contact.visitDate = TimeUtil.currentFormatDate()
            contact.fullName = contact.firstName + ' ' + contact.lastName
            if (contact.flag !== 'Cool' && contact.flag !== 'Warm' && contact.flag !== 'Hot') {
                contact.flag = 'Neutral';
            }
            addContact(db, contact).then(()=>{
                singleCountEmitter.emit('contacts_update')
                setTimeout(function () {
                    that.props.navigation.goBack();
                }, 50);
            })
        });
    }

    takePicture() {
        this.setState({
            showAction: true
        })
    }

    imageResponse(res) {
        this.setState({showAction:false})
        // console.log('imageResponse', res);
        if (res.errorCode && res.errorCode.length > 0) {
            Toast.showWithGravity(res.errorMessage, 2,Toast.CENTER);
            return;
        }
        if (res && res.assets && res.assets.length > 0) {
            let {contact} = this.state;
            contact.imgCard = res.assets[0].base64;
            this.setState({
                contact:contact
            })
        }
    }

    handleShowModal() {
        this.setState({
            showPictureModal: true
        })
    }

    handleHideModel() {
        this.setState({
            showPictureModal: false
        })
    }
}

const styles = StyleSheet.create({
    screen: {
      width: width,
      height: height
    },
    container: {
        flex: 1,
        flexDirection: 'column',
        justifyContent: 'flex-start',
        alignItems: 'center',
        backgroundColor: '#fff'
    },
    divider: {
        width: width,
        height: 1 / PixelRatio.get(),
        backgroundColor: Global.dividerColor
    },
    item:{
        width:width,
        marginBottom: 10,
        flexDirection:'row',
        justifyContent:'center',
        alignItems:'center'
    },
    item1:{
        marginTop:10
    },
    label:{
        color: '#000000',
        marginRight:10,
        width: 100,
        textAlign: 'right'
    },
    input:{
        borderColor: '#dddddd',
        borderWidth: 1,
        height: 40,
        color: '#000000',
        width: width - 150,
        paddingLeft: 5,
        paddingRight: 5
    },
    cardBg:{
        width: width - 150,
        flexDirection: 'column'
    },
    cardContainer:{
        width: '100%',
        height: 40,
        justifyContent:'center',
    },
    cardBtn:{
        borderRadius: 4,
        width: 100,
        height: 30,
        lineHeight: 30,
        textAlign: 'center',
        alignItems:'center',
        justifyContent:'center',
        backgroundColor: '#1e80ff',
        color: '#ffffff',
    },
    imgCard:{
        width: '100%',
        height: 150,
        resizeMode: 'contain'
    },
    remarkBtn:{
        borderRadius: 8,
        width: '30%',
        height: 30,
        alignItems:'center',
        justifyContent:'center'
    },
    flag:{
        fontWeight: 'bold',
        fontSize: 16
    },
    cool: {
        backgroundColor: '#0098e8',
    },
    warm: {
        backgroundColor: '#ffb621',
    },
    hot: {
        backgroundColor: '#ff0000',
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
    remarkContainer: {
        width: width*0.8,
        flexDirection: 'row',
        justifyContent: 'space-around',
        alignItems: 'center',
        marginTop:20
    },
    remarkText:{
        width:width/2,
        flexDirection: 'row',
        justifyContent: 'center',
        alignItems: 'center',
        marginTop:20
    },
    remarkTitle:{
        color:'#000',
        marginRight: 10,
        width: 100,
        textAlign: 'right'
    },
    remark: {
        width: width - 150,
        height: 80,
        borderColor: '#ccc',
        borderWidth: 1,
        alignItems:'flex-start',
        textAlignVertical:'top',
        color:'#000',
        paddingLeft: 5,
        paddingRight: 5
    },
    btn:{
        width: 150,
        height: 40,
        alignItems:'center',
        justifyContent:'center',
        borderRadius:8
    },
    submit:{
        backgroundColor:'#1e80ff',
        marginTop: 20
    }
});
