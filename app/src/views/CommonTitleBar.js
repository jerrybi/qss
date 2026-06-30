import React, {Component} from 'react';
import Global from '../utils/Global';
import Utils from '../utils/Utils';
import Icon from 'react-native-vector-icons/FontAwesome';
import CountEmitter from '../event/CountEmitter';
import {
    Button,
    Dimensions,
    Image,
    PixelRatio,
    StatusBar,
    StyleSheet,
    Text,
    TouchableOpacity,
    View,
    Platform
} from 'react-native';

const {width} = Dimensions.get('window');

export default class CommonTitleBar extends Component {
    constructor(props) {
        super(props);
    }

    renderAndroid() {
        return (
            <View style={styles.container}>
                <StatusBar
                    backgroundColor='#393A3E'
                    barStyle="light-content"
                />
                <View style={styles.content}>
                    {this.props.isBack ? (
                    <TouchableOpacity activeOpacity={0.5} onPress={this.handleBackClick}>
                        <View style={{width:60,height:30}}>
                            <Icon name="angle-left" size={30} style={styles.backBtn}/>
                        </View>
                    </TouchableOpacity>
                    ) : (null)}
                    {/*<View style={styles.btnDivider}/>*/}
                    <View style={styles.titleContainer}>
                        <Text style={styles.title}>{this.props.title}</Text>
                        {
                            Utils.isEmpty(this.props.rightIcon) ? (null) : (
                                Utils.isEmpty(this.props.rightIconStyle)?(
                                    <TouchableOpacity
                                        style={{padding: 15}}
                                        activeOpacity={0.6}
                                        onPress={() => this.handleRightClick()
                                    }>
                                        <Image style={styles.img} source={this.props.rightIcon}/>
                                    </TouchableOpacity>
                                ):(
                                    <TouchableOpacity
                                        style={{padding: 15}}
                                        activeOpacity={0.6}
                                        onPress={() => this.handleRightClick()
                                    }>
                                        <Image style={this.props.rightIconStyle} source={this.props.rightIcon}/>
                                    </TouchableOpacity>
                                )
                            )
                        }
                        {
                            Utils.isEmpty(this.props.rightBtnText) ? (null) : (
                                <Button
                                    onPress={() => this.props.handleRightBtnClick()}
                                    title={this.props.rightBtnText}
                                    color="#19AD17"
                                />
                            )
                        }
                    </View>
                </View>
            </View>
        );
    }

    renderIOS() {
        return (
            <View style={styles.container}>
                <View style={{height: 20, backgroundColor: Global.titleBackgroundColor}}/>
                <View style={styles.content}>
                    {this.props.isBack ? (
                        <TouchableOpacity activeOpacity={0.5} onPress={this.handleBackClick}>
                            <View style={{width:60,height:30}}>
                                <Icon name="angle-left" size={30} style={styles.backBtn}/>
                            </View>
                        </TouchableOpacity>
                    ) : (null)}
                    {/*<View style={styles.btnDivider}/>*/}
                    <View style={styles.titleContainer}>
                        <Text style={styles.title}>{this.props.title}</Text>
                        {
                            Utils.isEmpty(this.props.rightIcon) ? (null) : (
                                <TouchableOpacity activeOpacity={0.6} onPress={() => this.handleRightClick()}>
                                    <Image style={styles.img} source={this.props.rightIcon}/>
                                </TouchableOpacity>
                            )
                        }
                        {
                            Utils.isEmpty(this.props.rightBtnText) ? (null) : (
                                <Button
                                    onPress={() => this.props.handleRightBtnClick()}
                                    title={this.props.rightBtnText}
                                    color="#19AD17"
                                />
                            )
                        }
                    </View>
                </View>
            </View>
        );
    }

    render() {
        if (Platform.OS === 'ios') {
            return this.renderIOS();
        }
        return this.renderAndroid();
    }

    handleRightClick() {
        if (!Utils.isEmpty(this.props.handleRightClick)) {
            this.props.handleRightClick();
        }
    }

    handleBackClick = () => {
        console.log('handleBackClick')
        // CountEmitter.emit("goback");
        if(!Utils.isEmpty(this.props.nav.getParam('callback', null))){
            this.props.nav.state.params.callback();
        }
        this.props.nav.goBack();
    }
}

const styles = StyleSheet.create({
    container: {
        flexDirection: 'column',
    },
    content: {
        width: width,
        height: 40,
        backgroundColor: Global.titleBackgroundColor,
        flexDirection: 'row',
        alignItems: 'center'
    },
    backBtn: {
        color:"rgba(51,51,51,0.3)",
        marginLeft: 10,
    },
    btnDivider: {
        width: 1 / PixelRatio.get(),
        height: 30,
        marginTop: 10,
        marginBottom: 10,
        backgroundColor: '#888888'
    },
    titleContainer: {
        flex: 1,
        flexDirection: 'row',
        alignItems: 'center',
        paddingLeft: 10,
        paddingRight: 10,
    },
    title: {
        color: '#333333',
        fontSize: 18,
        fontWeight:"400",
        flex: 1,
        textAlign:"center"
    },
    img: {
        width: 30,
        height: 30,
        marginRight: 5
    }
});
