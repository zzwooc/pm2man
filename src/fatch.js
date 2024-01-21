import { message } from 'ant-design-vue'

class Fatch {
  static async request(url, options) {
    try {
      const baseUrl = import.meta.env.VITE_API_BASE_URL
      const response = await fetch(`${baseUrl}${url}`, options)
      if (!response.ok) {
        throw new Error(`请求失败，状态码：${response.status}`)
      }
      return await response.json()
    } catch (error) {
      console.error(error)
      message.error(error.message)
      throw new Error('发生错误，请重试')
    }
  }

  static async get(url) {
    return await Fatch.request(url, {
      method: 'GET',
    })
  }

  static async post(url, data) {
    return await Fatch.request(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    })
  }

  static async put(url, data) {
    return await Fatch.request(url, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    })
  }

  static async patch(url, data) {
    return await Fatch.request(url, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    })
  }

  static async delete(url) {
    return await Fatch.request(url, {
      method: 'DELETE',
    })
  }
}

export default Fatch
