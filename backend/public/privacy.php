<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人数据管理 - 数据删除请求</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <style>
        .fade-enter-active, .fade-leave-active {
            transition: opacity 0.3s ease;
        }
        .fade-enter-from, .fade-leave-to {
            opacity: 0;
        }
    </style>
</head>

<body class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen text-gray-800">
    <div id="app" class="pb-16">
        <header class="bg-white shadow-sm border-b border-gray-100">
            <div class="container mx-auto px-4 md:px-6 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center">
                        <i class="ri-shield-user-line text-white text-xl"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold">个人数据管理中心</h1>
                        <p class="text-xs text-gray-500">您的数据，您做主</p>
                    </div>
                </div>
                <a href="/" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    <i class="ri-arrow-left-line mr-1"></i>返回首页
                </a>
            </div>
        </header>

        <main class="container mx-auto px-4 md:px-6 py-8 md:py-12">
            <div class="max-w-3xl mx-auto">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-8 text-white">
                        <h2 class="text-2xl font-bold mb-2">申请删除您的个人数据</h2>
                        <p class="text-blue-100 text-sm">根据《个人信息保护法》，您有权申请删除或匿名化您的个人信息</p>
                    </div>

                    <div class="p-6 md:p-8">
                        <div v-if="step === 1" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">请选择查询方式</label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <button @click="queryType = 'email'"
                                        :class="['p-4 rounded-xl border-2 text-left transition-all', queryType === 'email' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300']">
                                        <div class="flex items-center space-x-3">
                                            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', queryType === 'email' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-500']">
                                                <i class="ri-mail-line text-xl"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium">邮箱地址</div>
                                                <div class="text-xs text-gray-500">使用您注册时的邮箱</div>
                                            </div>
                                        </div>
                                    </button>
                                    <button @click="queryType = 'visitor_id'"
                                        :class="['p-4 rounded-xl border-2 text-left transition-all', queryType === 'visitor_id' ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300']">
                                        <div class="flex items-center space-x-3">
                                            <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', queryType === 'visitor_id' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-500']">
                                                <i class="ri-id-card-line text-xl"></i>
                                            </div>
                                            <div>
                                                <div class="font-medium">访客编号</div>
                                                <div class="text-xs text-gray-500">使用系统分配的访客ID</div>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>

                            <div v-if="queryType === 'email'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">邮箱地址</label>
                                <input type="email" v-model="queryForm.email"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="请输入您的邮箱地址">
                            </div>

                            <div v-if="queryType === 'visitor_id'">
                                <label class="block text-sm font-medium text-gray-700 mb-2">访客编号</label>
                                <input type="number" v-model="queryForm.visitor_id"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="请输入您的访客编号">
                            </div>

                            <button @click="queryData" :disabled="isQuerying"
                                class="w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-medium hover:shadow-lg hover:shadow-blue-500/25 transition-all disabled:opacity-50">
                                <i v-if="isQuerying" class="ri-loader-4-line animate-spin mr-2"></i>
                                {{ isQuerying ? '查询中...' : '查询我的数据' }}
                            </button>

                            <div class="pt-6 border-t border-gray-100">
                                <p class="text-sm text-gray-500">
                                    <i class="ri-information-line text-blue-500 mr-1"></i>
                                    我们将根据您提供的信息，查询系统中存储的与您相关的所有数据记录。
                                </p>
                            </div>
                        </div>

                        <div v-if="step === 2" class="space-y-6">
                            <div v-if="!queryResult.found" class="text-center py-8">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="ri-search-line text-3xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-700 mb-2">未找到相关数据</h3>
                                <p class="text-gray-500 text-sm mb-6">{{ queryResult.message }}</p>
                                <button @click="resetForm" class="text-blue-600 hover:text-blue-800 font-medium">
                                    <i class="ri-arrow-left-line mr-1"></i>重新查询
                                </button>
                            </div>

                            <div v-if="queryResult.found" class="space-y-6">
                                <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                                    <div class="flex items-start space-x-3">
                                        <i class="ri-checkbox-circle-fill text-green-500 text-xl mt-0.5"></i>
                                        <div>
                                            <div class="font-medium text-green-800">查询成功</div>
                                            <div class="text-sm text-green-600">共找到 {{ totalRecords }} 条与您相关的数据记录</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium flex items-center">
                                                <i class="ri-eye-line text-blue-500 mr-2"></i>
                                                浏览记录
                                            </h4>
                                            <span class="text-sm bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">
                                                {{ queryResult.data.visitors.length }} 条
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 space-y-1 max-h-32 overflow-y-auto">
                                            <div v-for="item in queryResult.data.visitors.slice(0, 5)" :key="item.id"
                                                class="bg-white rounded-lg p-2 flex justify-between">
                                                <span>#{{ item.id }} - {{ item.city || '未知位置' }}</span>
                                                <span class="text-gray-400">{{ item.created_at }}</span>
                                            </div>
                                            <div v-if="queryResult.data.visitors.length > 5" class="text-center text-gray-400 py-1">
                                                ... 还有 {{ queryResult.data.visitors.length - 5 }} 条记录
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium flex items-center">
                                                <i class="ri-shield-check-line text-purple-500 mr-2"></i>
                                                同意记录
                                            </h4>
                                            <span class="text-sm bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                                                {{ queryResult.data.consent_records.length }} 条
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 space-y-1 max-h-32 overflow-y-auto">
                                            <div v-for="item in queryResult.data.consent_records" :key="item.id"
                                                class="bg-white rounded-lg p-2 flex justify-between">
                                                <span>{{ item.consent_type }} - {{ item.consent_value === 'granted' ? '已同意' : '已拒绝' }}</span>
                                                <span class="text-gray-400">{{ item.created_at }}</span>
                                            </div>
                                            <div v-if="queryResult.data.consent_records.length === 0" class="text-center text-gray-400 py-1">
                                                暂无同意记录
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium flex items-center">
                                                <i class="ri-download-line text-green-500 mr-2"></i>
                                                导出历史
                                            </h4>
                                            <span class="text-sm bg-green-100 text-green-700 px-2 py-0.5 rounded-full">
                                                {{ queryResult.data.export_history.length }} 条
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 space-y-1 max-h-32 overflow-y-auto">
                                            <div v-for="item in queryResult.data.export_history" :key="item.id"
                                                class="bg-white rounded-lg p-2 flex justify-between">
                                                <span>{{ item.export_type }} ({{ item.export_format }})</span>
                                                <span class="text-gray-400">{{ item.created_at }}</span>
                                            </div>
                                            <div v-if="queryResult.data.export_history.length === 0" class="text-center text-gray-400 py-1">
                                                暂无导出历史
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gray-50 rounded-xl p-4">
                                        <div class="flex items-center justify-between mb-3">
                                            <h4 class="font-medium flex items-center">
                                                <i class="ri-sticky-note-line text-yellow-500 mr-2"></i>
                                                备注信息
                                            </h4>
                                            <span class="text-sm bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-full">
                                                {{ queryResult.data.remarks.length }} 条
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 space-y-1 max-h-32 overflow-y-auto">
                                            <div v-for="item in queryResult.data.remarks" :key="item.visitor_id"
                                                class="bg-white rounded-lg p-2">
                                                <span class="text-gray-700">{{ item.remark }}</span>
                                                <span class="text-gray-400 ml-2">{{ item.created_at }}</span>
                                            </div>
                                            <div v-if="queryResult.data.remarks.length === 0" class="text-center text-gray-400 py-1">
                                                暂无备注信息
                                            </div>
                                        </div>
                                    </div>

                                    <div v-if="queryResult.retention_info.retainable_count > 0"
                                        class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                                        <div class="flex items-start space-x-3">
                                            <i class="ri-alert-line text-amber-500 text-xl mt-0.5"></i>
                                            <div>
                                                <div class="font-medium text-amber-800 mb-2">关于汇总统计数据</div>
                                                <div class="text-sm text-amber-700 space-y-2">
                                                    <p>系统中有 {{ queryResult.retention_info.retainable_count }} 份汇总统计报表无法删除，原因如下：</p>
                                                    <p class="bg-amber-100/50 p-3 rounded-lg text-amber-800">
                                                        {{ queryResult.retention_info.reasons.aggregated_reports }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-gray-100">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">请选择处理方式</label>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                                        <button @click="requestType = 'delete'"
                                            :class="['p-4 rounded-xl border-2 text-left transition-all', requestType === 'delete' ? 'border-red-500 bg-red-50' : 'border-gray-200 hover:border-gray-300']">
                                            <div class="flex items-center space-x-3">
                                                <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', requestType === 'delete' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-500']">
                                                    <i class="ri-delete-bin-line text-xl"></i>
                                                </div>
                                                <div>
                                                    <div class="font-medium">完全删除</div>
                                                    <div class="text-xs text-gray-500">彻底删除所有个人数据</div>
                                                </div>
                                            </div>
                                        </button>
                                        <button @click="requestType = 'anonymize'"
                                            :class="['p-4 rounded-xl border-2 text-left transition-all', requestType === 'anonymize' ? 'border-orange-500 bg-orange-50' : 'border-gray-200 hover:border-gray-300']">
                                            <div class="flex items-center space-x-3">
                                                <div :class="['w-10 h-10 rounded-lg flex items-center justify-center', requestType === 'anonymize' ? 'bg-orange-500 text-white' : 'bg-gray-100 text-gray-500']">
                                                    <i class="ri-user-unfollow-line text-xl"></i>
                                                </div>
                                                <div>
                                                    <div class="font-medium">匿名化处理</div>
                                                    <div class="text-xs text-gray-500">保留数据但移除个人标识</div>
                                                </div>
                                            </div>
                                        </button>
                                    </div>

                                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
                                        <label class="flex items-start space-x-3 cursor-pointer">
                                            <input type="checkbox" v-model="agreed"
                                                class="mt-1 w-4 h-4 text-blue-600 rounded focus:ring-blue-500">
                                            <span class="text-sm text-blue-800">
                                                我已了解并同意：数据删除后将无法恢复，管理员将在1-3个工作日内处理我的申请。
                                                处理完成后，我将收到一份可下载的处理回执。
                                            </span>
                                        </label>
                                    </div>

                                    <div class="flex space-x-4">
                                        <button @click="step = 1"
                                            class="flex-1 py-3 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition">
                                            返回修改
                                        </button>
                                        <button @click="submitRequest" :disabled="!agreed || isSubmitting"
                                            class="flex-1 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-medium hover:shadow-lg hover:shadow-blue-500/25 transition-all disabled:opacity-50">
                                            <i v-if="isSubmitting" class="ri-loader-4-line animate-spin mr-2"></i>
                                            {{ isSubmitting ? '提交中...' : '提交申请' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-if="step === 3" class="text-center py-8">
                            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                                <i class="ri-check-double-line text-4xl text-green-500"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">申请提交成功</h3>
                            <p class="text-gray-500 mb-6">您的数据删除申请已提交，我们将尽快处理</p>

                            <div class="bg-gray-50 rounded-xl p-6 text-left mb-6 max-w-md mx-auto">
                                <div class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">申请编号</span>
                                        <span class="font-mono font-medium text-blue-600">{{ submitResult.request_code }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">申请类型</span>
                                        <span class="font-medium">{{ requestType === 'delete' ? '完全删除' : '匿名化处理' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-500">申请状态</span>
                                        <span class="text-yellow-600 font-medium">待处理</span>
                                    </div>
                                </div>
                            </div>

                            <p class="text-sm text-gray-500 mb-6">
                                <i class="ri-information-line text-blue-500 mr-1"></i>
                                请妥善保管您的申请编号，可用于查询处理进度和下载回执
                            </p>

                            <div class="flex flex-col sm:flex-row justify-center space-y-3 sm:space-y-0 sm:space-x-4">
                                <button @click="checkReceipt"
                                    class="px-6 py-2 border border-blue-500 text-blue-600 rounded-xl font-medium hover:bg-blue-50 transition">
                                    <i class="ri-file-list-3-line mr-1"></i>查询回执
                                </button>
                                <button @click="resetForm"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-xl font-medium hover:bg-blue-700 transition">
                                    <i class="ri-add-line mr-1"></i>提交新申请
                                </button>
                            </div>
                        </div>

                        <div v-if="step === 4" class="space-y-6">
                            <h3 class="text-lg font-bold flex items-center">
                                <i class="ri-file-list-3-line text-blue-500 mr-2"></i>
                                查询处理回执
                            </h3>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">申请编号或回执编号</label>
                                <input type="text" v-model="receiptQueryCode"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="请输入申请编号或回执编号">
                            </div>

                            <button @click="queryReceipt" :disabled="isQueryingReceipt"
                                class="w-full py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-xl font-medium hover:shadow-lg hover:shadow-blue-500/25 transition-all disabled:opacity-50">
                                <i v-if="isQueryingReceipt" class="ri-loader-4-line animate-spin mr-2"></i>
                                {{ isQueryingReceipt ? '查询中...' : '查询回执' }}
                            </button>

                            <div v-if="receiptData" class="bg-gray-50 rounded-xl p-6">
                                <h4 class="font-medium mb-4">处理回执</h4>
                                <pre class="bg-white rounded-lg p-4 text-xs font-mono whitespace-pre-wrap border border-gray-200 max-h-64 overflow-y-auto">{{ receiptData.receipt.content }}</pre>
                                <button @click="downloadReceipt"
                                    class="mt-4 w-full py-2 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition">
                                    <i class="ri-download-line mr-1"></i>下载回执
                                </button>
                            </div>

                            <button @click="step = 1"
                                class="w-full py-2 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 transition">
                                <i class="ri-arrow-left-line mr-1"></i>返回申请页面
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-8 text-center text-sm text-gray-500">
                    <p>如有疑问，请联系我们的隐私官：privacy@example.com</p>
                </div>
            </div>
        </main>
    </div>

    <script>
        const { createApp, ref, computed } = Vue;

        createApp({
            setup() {
                const step = ref(1);
                const queryType = ref('email');
                const isQuerying = ref(false);
                const isSubmitting = ref(false);
                const isQueryingReceipt = ref(false);
                const agreed = ref(false);
                const requestType = ref('delete');
                const receiptQueryCode = ref('');

                const queryForm = ref({
                    email: '',
                    visitor_id: ''
                });

                const queryResult = ref(null);
                const submitResult = ref(null);
                const receiptData = ref(null);

                const totalRecords = computed(() => {
                    if (!queryResult.value?.found) return 0;
                    const d = queryResult.value.data;
                    return d.visitors.length + d.consent_records.length + d.export_history.length + d.remarks.length;
                });

                const queryData = async () => {
                    if (queryType.value === 'email' && !queryForm.value.email) {
                        alert('请输入邮箱地址');
                        return;
                    }
                    if (queryType.value === 'visitor_id' && !queryForm.value.visitor_id) {
                        alert('请输入访客编号');
                        return;
                    }

                    isQuerying.value = true;
                    try {
                        const res = await fetch('/api.php?action=gdpr_query', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                email: queryType.value === 'email' ? queryForm.value.email : '',
                                visitor_id: queryType.value === 'visitor_id' ? queryForm.value.visitor_id : ''
                            })
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            queryResult.value = json;
                            step.value = 2;
                        } else {
                            alert('查询失败：' + json.message);
                        }
                    } catch (e) {
                        alert('查询出错：' + e.message);
                    } finally {
                        isQuerying.value = false;
                    }
                };

                const submitRequest = async () => {
                    if (!agreed.value) {
                        alert('请阅读并同意相关条款');
                        return;
                    }

                    isSubmitting.value = true;
                    try {
                        const res = await fetch('/api.php?action=gdpr_submit', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                email: queryType.value === 'email' ? queryForm.value.email : '',
                                visitor_id: queryType.value === 'visitor_id' ? queryForm.value.visitor_id : '',
                                request_type: requestType.value
                            })
                        });
                        const json = await res.json();
                        if (json.status === 'success') {
                            submitResult.value = json;
                            step.value = 3;
                        } else {
                            alert('提交失败：' + json.message);
                        }
                    } catch (e) {
                        alert('提交出错：' + e.message);
                    } finally {
                        isSubmitting.value = false;
                    }
                };

                const checkReceipt = () => {
                    receiptQueryCode.value = submitResult.value?.request_code || '';
                    step.value = 4;
                };

                const queryReceipt = async () => {
                    if (!receiptQueryCode.value) {
                        alert('请输入申请编号或回执编号');
                        return;
                    }

                    isQueryingReceipt.value = true;
                    try {
                        const isReceipt = receiptQueryCode.value.startsWith('RCP');
                        const url = isReceipt
                            ? `/api.php?action=gdpr_receipt&code=${receiptQueryCode.value}`
                            : `/api.php?action=gdpr_receipt&request_code=${receiptQueryCode.value}`;
                        const res = await fetch(url);
                        const json = await res.json();
                        if (json.status === 'success') {
                            receiptData.value = json;
                        } else {
                            alert('查询失败：' + json.message);
                            receiptData.value = null;
                        }
                    } catch (e) {
                        alert('查询出错：' + e.message);
                    } finally {
                        isQueryingReceipt.value = false;
                    }
                };

                const downloadReceipt = () => {
                    if (!receiptData.value) return;
                    const code = receiptData.value.receipt.receipt_code;
                    window.open(`/api.php?action=gdpr_receipt&code=${code}&format=download`, '_blank');
                };

                const resetForm = () => {
                    step.value = 1;
                    queryForm.value = { email: '', visitor_id: '' };
                    queryResult.value = null;
                    submitResult.value = null;
                    receiptData.value = null;
                    agreed.value = false;
                    requestType.value = 'delete';
                };

                return {
                    step, queryType, isQuerying, isSubmitting, isQueryingReceipt,
                    agreed, requestType, queryForm, queryResult, submitResult, receiptData,
                    receiptQueryCode, totalRecords,
                    queryData, submitRequest, checkReceipt, queryReceipt, downloadReceipt, resetForm
                };
            }
        }).mount('#app');
    </script>
</body>

</html>
