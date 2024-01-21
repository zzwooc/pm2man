<script setup>
  import { onMounted, ref, h } from 'vue'
  import { formatTimeAgo } from '@vueuse/core'
  import {
    ReloadOutlined,
    UploadOutlined,
    ExceptionOutlined,
    PlusOutlined,
    PlayCircleOutlined,
    PauseCircleOutlined,
    DeleteOutlined,
    SaveOutlined,
  } from '@ant-design/icons-vue'
  import { Modal, message, theme } from 'ant-design-vue'
  import Fatch from './fatch'

  // 主题配置和切换
  const darkTheme = ref(false)
  const currentTheme = ref(theme.defaultAlgorithm)

  const changeTheme = () => {
    darkTheme.value = !darkTheme.value
    currentTheme.value = darkTheme.value
      ? theme.darkAlgorithm
      : theme.defaultAlgorithm
    localStorage.setItem('darkTheme', darkTheme.value)
  }

  // 表格头配置
  const columns = [
    {
      title: 'ID',
      key: 'id',
      dataIndex: 'id',
      defaultSortOrder: 'descend',
      sortDirections: ['ascend', 'descend'],
      sorter: {
        compare: (a, b) => a.id - b.id,
      },
    },
    {
      title: 'Name',
      key: 'name',
      dataIndex: 'name',
      sortDirections: ['ascend', 'descend'],
      sorter: {
        compare: (a, b) => a.name.localeCompare(b.name),
      },
    },
    {
      title: '运行模式',
      key: 'mode',
      dataIndex: 'mode',
      filters: [
        {
          text: '单例',
          value: 'fork_mode',
        },
        {
          text: '集群',
          value: 'cluster_mode',
        },
      ],
      filterMultiple: true,
      onFilter: (value, record) => record.mode.indexOf(value) === 0,
      sortDirections: ['ascend', 'descend'],
      sorter: {
        compare: (a, b) => a.mode.localeCompare(b.mode),
      },
    },
    {
      title: '启动时间',
      key: 'uptime',
      dataIndex: 'uptime',
      sortDirections: ['ascend', 'descend'],
      sorter: {
        compare: (a, b) => a.uptime - b.uptime,
      },
    },
    {
      title: '重启次数',
      key: 'restarts',
      dataIndex: 'restarts',
      sortDirections: ['ascend', 'descend'],
      sorter: {
        compare: (a, b) => a.restarts - b.restarts,
      },
    },
    {
      title: '状态',
      key: 'status',
      dataIndex: 'status',
      filters: [
        {
          text: '运行中',
          value: 'online',
        },
        {
          text: '已停止',
          value: 'stopped',
        },
      ],
      filterMultiple: true,
      onFilter: (value, record) => record.status.indexOf(value) === 0,
      sortDirections: ['ascend', 'descend'],
      sorter: {
        compare: (a, b) => a.status.localeCompare(b.status),
      },
    },
    {
      title: 'CPU',
      key: 'cpu',
      dataIndex: 'cpu',
      sortDirections: ['ascend', 'descend'],
      sorter: {
        compare: (a, b) => a.cpu - b.cpu,
      },
    },
    {
      title: '内存',
      key: 'mem',
      dataIndex: 'mem',
      sortDirections: ['ascend', 'descend'],
      sorter: {
        compare: (a, b) => a.mem - b.mem,
      },
    },
    {
      title: 'Watching',
      key: 'watch',
      dataIndex: 'watch',
      sortDirections: ['ascend', 'descend'],
      sorter: {
        compare: (a, b) => a.watch.localeCompare(b.watch),
      },
    },
    {
      title: '运行脚本',
      key: 'file',
      dataIndex: 'file',
    },
    {
      title: '运行参数',
      key: 'args',
      dataIndex: 'args',
    },
    {
      title: '操作',
      key: 'action',
    },
  ]

  // 表格数据
  const data = ref([])
  onMounted(async () => {
    // 初始化主题
    if (localStorage.getItem('darkTheme') === 'true') {
      darkTheme.value = true
      currentTheme.value = theme.darkAlgorithm
    } else {
      darkTheme.value = false
      currentTheme.value = theme.defaultAlgorithm
    }
    // 初始化表哥数据
    data.value = await Fatch.get('/pm2/jlist')
  })

  // 启动所有应用
  const starting = ref(false)
  const startAll = async () => {
    starting.value = true
    await Fatch.post('/pm2/start/all')
    data.value = await Fatch.get('/pm2/jlist')
    message.success('所有应用启动成功')
    starting.value = false
  }
  const confirmStartAll = () => {
    Modal.confirm({
      title: '确定要启动所有应用吗？',
      cancelText: '点错了',
      okText: '我确定',
      onOk() {
        startAll()
      },
    })
  }

  // 停止所有应用
  const stopping = ref(false)
  const stopAll = async () => {
    stopping.value = true
    await Fatch.post('/pm2/stop/all')
    data.value = await Fatch.get('/pm2/jlist')
    message.success('所有应用停止成功')
    stopping.value = false
  }
  const confirmStopAll = () => {
    Modal.confirm({
      title: '确定要停止所有应用吗？',
      cancelText: '点错了',
      okText: '我确定',
      onOk() {
        stopAll()
      },
    })
  }

  // 删除所有应用
  const deleting = ref(false)
  const deleteAll = async () => {
    deleting.value = true
    await Fatch.delete('/pm2/delete/all')
    data.value = await Fatch.get('/pm2/jlist')
    message.success('所有应用删除成功')
    deleting.value = false
  }
  const confirmDeleteAll = () => {
    Modal.confirm({
      title: '确定要删除所有应用吗？',
      cancelText: '点错了',
      okText: '我确定',
      onOk() {
        deleteAll()
      },
    })
  }

  // 查看某一个应用详细信息
  const showed = ref(false)
  const showData = ref({})
  const show = async (name) => {
    showData.value = {}
    showed.value = true
    showData.value = await Fatch.get(`/pm2/show/${name}`)
  }

  // 查看某一个应用的日志
  const logsShow = ref(false)
  const logsData = ref({})
  const logsName = ref('')
  const lines = ref(100)
  const logs = async (name) => {
    logsData.value = {}
    logsShow.value = true
    logsName.value = name
    logsData.value = await Fatch.get(`/pm2/logs/${name}?lines=${lines.value}`)
  }

  // 启动
  const start = async (name) => {
    data.value.forEach((item, index) => {
      if (item.name === name) {
        data.value[index].starting = true
      }
    })

    await Fatch.post(`/pm2/start/${name}`)
    data.value = await Fatch.get('/pm2/jlist')
    message.success('启动成功')

    data.value.forEach((item, index) => {
      if (item.name === name) {
        data.value[index].starting = false
      }
    })
  }
  const confirmStart = (name) => {
    Modal.confirm({
      title: `确定要启动 ${name} 吗？`,
      cancelText: '点错了',
      okText: '我确定',
      onOk() {
        start(name)
      },
    })
  }

  // 停止
  const stop = async (name) => {
    data.value.forEach((item, index) => {
      if (item.name === name) {
        data.value[index].stopping = true
      }
    })

    await Fatch.post(`/pm2/stop/${name}`)
    data.value = await Fatch.get('/pm2/jlist')
    message.success('停止成功')

    data.value.forEach((item, index) => {
      if (item.name === name) {
        data.value[index].stopping = false
      }
    })
  }
  const confirmStop = (name) => {
    Modal.confirm({
      title: `确定要停止 ${name} 吗？`,
      cancelText: '点错了',
      okText: '我确定',
      onOk() {
        stop(name)
      },
    })
  }

  // 删除
  const delet = async (name) => {
    data.value.forEach((item, index) => {
      if (item.name === name) {
        data.value[index].deleting = true
      }
    })

    await Fatch.delete(`/pm2/delete/${name}`)
    data.value = await Fatch.get('/pm2/jlist')
    message.success('删除成功')

    data.value.forEach((item, index) => {
      if (item.name === name) {
        data.value[index].deleting = false
      }
    })
  }
  const confirmDelet = (name) => {
    Modal.confirm({
      title: `确定要删除 ${name} 吗？`,
      cancelText: '点错了',
      okText: '我确定',
      onOk() {
        delet(name)
      },
    })
  }

  // 上传
  const uploadFiles = ref([])
  const apiUrl = ref(`${import.meta.env.VITE_API_BASE_URL}`)
  const uploadApi = ref(`${apiUrl}/pm2/upload`)
  const uploadChange = ({ file }) => {
    formState.value.filename = file.name
  }

  const opened = ref(false)
  const formState = ref({
    filename: null,
    name: '',
    cluster: false,
    instances: 1,
    args: '',
  })
  const theForm = ref(null)
  const creating = ref(false)
  const create = async () => {
    if (!formState.value.filename) {
      message.error('请上传脚本文件！')
      return
    }
    if (!formState.value.name) {
      message.error('请填写运行名称！')
      return
    }
    creating.value = true
    await Fatch.post('/pm2/start', formState.value)
    data.value = await Fatch.get('/pm2/jlist')
    message.success('新建成功')
    creating.value = false
    reset()
  }
  const reset = () => {
    opened.value = false
    formState.value.filename = null
    formState.value.name = ''
    formState.value.cluster = false
    formState.value.instances = 1
    formState.value.args = ''
    uploadFiles.value = []
  }

  // 保存开机自启
  const saving = ref(false)
  const save = async () => {
    saving.value = true
    await Fatch.post('/pm2/save')
    message.success('保存成功')
    saving.value = false
  }
</script>

<template>
  <a-config-provider
    :theme="{
      algorithm: currentTheme,
    }"
  >
    <a-layout class="px-4 min-h-screen">
      <a-flex gap="middle" align="center" justify="space-between" class="py-2">
        <a-flex align="center" justify="center">
          <a-button type="link" @click="changeTheme">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              v-if="darkTheme"
            >
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="24"
              height="24"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              v-else
            >
              <circle cx="12" cy="12" r="5"></circle>
              <line x1="12" y1="1" x2="12" y2="3"></line>
              <line x1="12" y1="21" x2="12" y2="23"></line>
              <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
              <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
              <line x1="1" y1="12" x2="3" y2="12"></line>
              <line x1="21" y1="12" x2="23" y2="12"></line>
              <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
              <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
          </a-button>
          <a-typography-title
            :level="3"
            strong
            mark
            class="my-3 select-none cursor-default"
            >PM2MAN</a-typography-title
          >
        </a-flex>
        <a-flex gap="middle" align="center" justify="center" class="p-4">
          <a-button
            type="link"
            :icon="h(ExceptionOutlined)"
            @click="logs('all')"
          >
            查看所有日志
          </a-button>
          <a-button
            type="primary"
            :icon="h(PlusOutlined)"
            @click="opened = true"
            :disabled="opened"
          >
            新建应用并启动
          </a-button>
          <a-button
            type="primary"
            ghost
            :icon="h(PlayCircleOutlined)"
            @click="confirmStartAll"
            :loading="starting"
            :disabled="starting || stopping || deleting || saving"
          >
            启动所有应用
          </a-button>
          <a-button
            ghost
            danger
            :icon="h(PauseCircleOutlined)"
            @click="confirmStopAll"
            :loading="stopping"
            :disabled="starting || stopping || deleting || saving"
          >
            停止所有应用
          </a-button>
          <a-button
            type="primary"
            danger
            :icon="h(DeleteOutlined)"
            @click="confirmDeleteAll"
            :loading="deleting"
            :disabled="starting || stopping || deleting || saving"
          >
            删除所有应用
          </a-button>
          <a-button
            type="primary"
            :icon="h(SaveOutlined)"
            :loading="saving"
            :disabled="starting || stopping || deleting || saving"
            @click="save"
          >
            保存开机自启
          </a-button>
        </a-flex>
      </a-flex>
      <a-table
        :columns="columns"
        :data-source="data"
        :pagination="false"
        class="mb-4"
      >
        <template #headerCell="{ title }">
          <ReloadOutlined v-if="title === '重启次数'" />
        </template>

        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'id'">
            <a-button type="link" @click="logs(record.name)">
              {{ record.id }}
            </a-button>
          </template>
          <template v-if="column.key === 'name'">
            <a-button type="link" @click="show(record.name)">
              {{ record.name }}
            </a-button>
          </template>
          <template v-else-if="column.key === 'mode'">
            <a-tag color="blue" v-if="record.mode === 'fork_mode'">
              单例
            </a-tag>
            <a-flex
              gap="small"
              vertical
              align="center"
              justify="center"
              v-if="record.mode === 'cluster_mode'"
            >
              <a-tag color="orange"> 集群 </a-tag>
              <a-typography-text type="secondary">
                {{ record.instances }} 个实例
              </a-typography-text>
            </a-flex>
          </template>
          <template v-else-if="column.key === 'uptime'">
            {{ formatTimeAgo(record.uptime) }}
          </template>
          <template v-else-if="column.key === 'status'">
            <a-tag color="green" v-if="record.status === 'online'">
              运行中
            </a-tag>
            <a-tag color="red" v-if="record.status === 'stopped'">
              已停止
            </a-tag>
          </template>
          <template v-else-if="column.key === 'cpu'">
            <a-progress
              :percent="record.cpu"
              :stroke-width="5"
              status="active"
            ></a-progress>
          </template>
          <template v-else-if="column.key === 'mem'">
            <a-progress
              :percent="record.mem"
              :stroke-width="5"
              status="active"
            ></a-progress>
          </template>
          <template v-else-if="column.key === 'watch'">
            <a-tag color="green" v-if="record.watch">开启</a-tag>
            <a-tag v-else>关闭</a-tag>
          </template>
          <template v-else-if="column.key === 'file'">
            {{
              record.name !== 'pm2man' || record.name !== 'pm2srv'
                ? record.file
                : ''
            }}
          </template>
          <template v-else-if="column.key === 'args'">
            <a-flex gap="small" vertical align="center" justify="center">
              <a-tag v-for="(item, index) in record.args" :key="index">
                {{ item }}
              </a-tag>
            </a-flex>
          </template>
          <template v-else-if="column.key === 'action'">
            <a-space
              v-if="record.name !== 'pm2man' && record.name !== 'pm2srv'"
            >
              <a-button
                type="primary"
                ghost
                :icon="h(PlayCircleOutlined)"
                @click="confirmStart(record.name)"
                :loading="starting || record.starting"
                :disabled="
                  starting ||
                  stopping ||
                  deleting ||
                  record.starting ||
                  record.stopping ||
                  record.deleting
                "
                v-if="record.status !== 'online'"
              >
                启动
              </a-button>
              <a-button
                ghost
                danger
                :icon="h(PauseCircleOutlined)"
                @click="confirmStop(record.name)"
                :loading="stopping || record.stopping"
                :disabled="
                  starting ||
                  stopping ||
                  deleting ||
                  record.starting ||
                  record.stopping ||
                  record.deleting
                "
                v-if="record.status !== 'stopped'"
              >
                停止
              </a-button>
              <a-button
                type="primary"
                danger
                :icon="h(DeleteOutlined)"
                @click="confirmDelet(record.name)"
                :loading="deleting || record.deleting"
                :disabled="
                  starting ||
                  stopping ||
                  deleting ||
                  record.starting ||
                  record.stopping ||
                  record.deleting
                "
              >
                删除
              </a-button>
            </a-space>
          </template>
        </template>
      </a-table>
    </a-layout>
    <a-modal v-model:open="opened" :footer="null" title="新建应用">
      <a-form
        ref="theForm"
        :model="formState"
        :label-col="{
          style: {
            width: '80px',
          },
        }"
        class="mt-10"
      >
        <a-form-item
          label="上传脚本"
          :rules="[
            { required: true, message: '请上传脚本文件！', trigger: 'change' },
          ]"
        >
          <a-upload
            v-model:file-list="uploadFiles"
            name="file"
            :maxCount="1"
            :action="uploadApi"
            @change="uploadChange"
          >
            <a-button>
              <UploadOutlined></UploadOutlined>
              上传脚本文件
            </a-button>
          </a-upload>
        </a-form-item>
        <a-form-item
          label="运行名称"
          :rules="[
            { required: true, message: '请填写运行名称！', trigger: 'change' },
          ]"
        >
          <a-input v-model:value="formState.name" />
        </a-form-item>
        <a-form-item label="集群模式">
          <a-switch v-model:checked="formState.cluster" />
        </a-form-item>
        <a-form-item label="负载数量" v-if="formState.cluster">
          <a-input-number
            v-model:value="formState.instances"
            :min="1"
            :max="4"
          />
        </a-form-item>
        <a-form-item label="脚本参数">
          <a-input v-model:value="formState.args" />
        </a-form-item>
        <a-form-item :wrapper-col="{ span: 14, offset: 4 }">
          <a-button
            type="primary"
            @click="create"
            :disabled="!formState.filename || !formState.name"
            >新建</a-button
          >
          <a-button class="ml-2" @click="reset">取消</a-button>
        </a-form-item>
      </a-form>
    </a-modal>
    <a-modal
      v-model:open="showed"
      :footer="null"
      width="800px"
      wrap-class-name="full-modal"
    >
      <a-flex justify="center" class="mt-1">
        <a-typography-text class="whitespace-pre-wrap font-mono">
          {{ showData }}
        </a-typography-text>
      </a-flex>
    </a-modal>
    <a-modal
      v-model:open="logsShow"
      :footer="null"
      width="800px"
      wrap-class-name="full-modal"
    >
      <a-flex gap="small" align="center" class="mt-1">
        <a-typography-text strong>行数：</a-typography-text>
        <a-input-number
          v-model:value="lines"
          :min="1"
          :max="1000"
          class="w-20"
        />
        <a-button @click="logs(logsName)" :icon="h(ReloadOutlined)"></a-button>
      </a-flex>
      <a-divider />
      <a-flex justify="center" class="mt-4">
        <a-typography-text class="whitespace-pre-wrap font-mono">
          {{ logsData }}
        </a-typography-text>
      </a-flex>
    </a-modal>
  </a-config-provider>
</template>

<style scoped>
  .full-modal .ant-modal {
    max-width: 100%;
    top: 0;
    padding-bottom: 0;
    margin: 0;
  }
  .full-modal .ant-modal-content {
    display: flex;
    flex-direction: column;
    height: calc(100vh);
  }
  .full-modal .ant-modal-body {
    flex: 1;
  }
</style>
