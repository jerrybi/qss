import AsyncStorage from '@react-native-async-storage/async-storage';

export default class StorageUtil {
  /**
   * 获取
   * @param key
   * @returns {Promise<T>|*|Promise.<TResult>}
   */
  static get(key) {
    return AsyncStorage.getItem(key);
  }


  /**
   * 保存
   * @param key
   * @param value
   * @returns {*}
   */
  static set(key, value, callback) {
    return AsyncStorage.setItem(key, value, callback);
  }


  /**
   * 更新
   * @param key
   * @param value
   * @returns {Promise<T>|Promise.<TResult>}
   */
  static update(key, value) {
    StorageUtil.set(key, value);
  }


  /**
   * 删除
   * @param key
   * @returns {*}
   */
  static delete(key) {
    return AsyncStorage.removeItem(key);
  }

  /**
  * 清除所有Storage
  */
  static clear(callback) {
    AsyncStorage.clear(callback);
  }

  static async getUserId(){
    const user = await this.get('user');
    const userInfo = user != null ? JSON.parse(user) : null;
    return userInfo != null ? userInfo.id : null;
  }
}
