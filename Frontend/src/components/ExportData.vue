<template>
    <el-dialog v-model="visible" :close-on-press-escape="false" :close-on-click-modal="false" :show-close="false"
        @keyup.esc="$emit('invisible')" style="width:30%">
        <template #header>
            <div class="my-header">
                <h4>Export {{ prop.mode }}</h4>
                <el-button type="danger" @click="$emit('invisible')">
                    <el-icon class="el-icon--left">
                        <CircleCloseFilled />
                    </el-icon>
                    Close
                </el-button>
            </div>
        </template>
        <el-form>
            <el-form-item label="From Month" :label-width="formLabelWidth">
                <div class="block">
                    <el-date-picker v-model="form.fromMonth" type="month" placeholder="Pick a month" />
                </div>
            </el-form-item>
            <el-form-item label="To Month" :label-width="formLabelWidth">
                <div class="block">
                    <el-date-picker v-model="form.toMonth" type="month" placeholder="Pick a month" />
                </div>
            </el-form-item>
            <small>{{ checkDate }}</small>
        </el-form>
        <template #footer>
            <span class="dialog-footer">
                <el-button @click="$emit('invisible')">Cancel</el-button>
            </span>
            <el-button v-if="prop.mode === 'Excel'" type="primary" @click="exportExcel">
                Export Excel
            </el-button>
            <el-button v-else type="primary" @click="exportCSV">
                Export CSV
            </el-button>
        </template>
    </el-dialog>
</template>
  
<style scoped>
.my-header {
    display: flex;
    justify-content: space-between;
}

.user {
    display: flex;
    align-items: center;
    min-width: 30%
}

.dialog-footer {
    margin-top: 16px;
}

.dialog-footer button:first-child {
    margin-right: 10px;
}

.dialog-footer button {
    margin-right: 8px;
}

small {
    color: red;
    margin-top: 0.1rem;
    margin-left: 16px;
    font-size: 14px;
    color: red;
    margin-top: 0.1rem;
    margin-left: 0 px;
    font-size: 14px;
}
</style>
  
<script setup>
import { ref, defineProps, reactive, computed } from "vue";
import axios from "axios";
import * as XLSX from 'xlsx';
import * as Papa from 'papaparse'
import FileSaver from 'file-saver';
import router from "../router";
import { useUserStore } from "../stores/user";
import { useAlertStore } from "../stores/alert";
const prop = defineProps({
    userId: {
        type: Number
    },
    mode: {
        type: String
    }
})
let visible = ref(true)
const formLabelWidth = '150px'
const user = useUserStore().user
let username = ref('')
let wasClick = ref(false)
const alertStore = useAlertStore()
const emits = defineEmits(['invisible', 'updateData'])
const form = reactive({
    fromMonth: '',
    toMonth: ''
})
const checkDate = computed(() => {
    if ((form.frommonth == '' || form.toMonth == '') && wasClick.value == true) {
        return 'Please fill form'
    }
    else if (formatToPost(form.fromMonth, 'start') > formatToPost(form.toMonth, 'end') && wasClick.value == true) {
        return 'To date must be bigger than from date'
    } else {
        return ''
    }
})
const data = ref();
const exportExcel = async () => {
    let api = `http://127.0.0.1:8000/api/get-timekeeping-export/${formatToPost(form.fromMonth, 'start')}/${formatToPost(form.toMonth, 'end')}/${prop.userId}`;
    if (router.currentRoute.value.fullPath === '/schedule') {
        api = `http://127.0.0.1:8000/api/get-timekeeping-export/${formatToPost(form.fromMonth, 'start')}/${formatToPost(form.toMonth, 'end')}/${user.id}`
    }
    if (form.fromMonth != '' && form.toMonth != '') {
        if (formatToPost(form.fromMonth, 'start') < formatToPost(form.toMonth, 'end')) {
            try {
                await axios.get(api, {
                    headers: { Authorization: `Bearer ${user.token}` }
                }).then(function (response) {
                    data.value = response.data.data
                    username = response.data.data[0].user
                })
                const wb = XLSX.utils.book_new();
                const fromDate = new Date(form.fromMonth);
                const toDate = new Date(form.toMonth);
                const currentDate = new Date(fromDate);

                while (currentDate <= toDate) {
                    const currentMonth = currentDate.getMonth() + 1;
                    const currentYear = currentDate.getFullYear();
                    const ws = XLSX.utils.aoa_to_sheet([]);
                    const daysInMonth = new Date(currentDate.getFullYear(), currentMonth, 0).getDate();
                    const daysArray = Array.from({ length: daysInMonth }, (_, i) => i + 1);
                    const headerRow = ['Date', 'Day of week', 'Time check in', 'Time check out', 'Shift', 'Status AM', 'Status PM', 'Time work', 'User name'];
                    const rows = daysArray.map(day => {
                        const dataForDay = data.value.find(item => {
                            const itemDate = new Date(item.date);
                            return itemDate.getMonth() + 1 === currentMonth && itemDate.getDate() === day;
                        });
                        const rowData = [day, getDayOfWeek(currentDate, day)];
                        if (dataForDay) {
                            rowData.push(
                                dataForDay['timeCheckIn'],
                                dataForDay['timeCheckOut'],
                                dataForDay['shift'],
                                statusWork(dataForDay['status_AM']),
                                statusWork(dataForDay['status_PM']),
                                dataForDay['timeWork'],
                                username
                            );
                        } else {
                            rowData.push('00:00 (00:00)', '00:00 (00:00)', 'OFF', 'OFF', 'OFF', '00:00', username);
                        }
                        return rowData;
                    });
                    rows.unshift(headerRow);
                    if (prop.mode == 'Excel') {
                        if (!ws['!cols']) {
                            ws['!cols'] = [];
                        }
                        ws['!cols'][0] = { wch: 5 };
                        ws['!cols'][1] = { wch: 10 };
                        ws['!cols'][2] = { wch: 15 };
                        ws['!cols'][3] = { wch: 15 };
                        ws['!cols'][4] = { wch: 5 };
                        ws['!cols'][5] = { wch: 10 };
                        ws['!cols'][6] = { wch: 10 };
                        ws['!cols'][7] = { wch: 10 };
                        ws['!cols'][8] = { wch: 15 };
                        XLSX.utils.sheet_add_aoa(ws, rows, { origin: 'A1' });
                        XLSX.utils.book_append_sheet(wb, ws, `${currentMonth} - ${currentYear}`);
                        currentDate.setMonth(currentDate.getMonth() + 1);
                    }
                }
                XLSX.writeFile(wb, 'data.xlsx');
                messages('success', 'Export complete')
                emits('invisible');
            } catch (e) {
                messages('error', e.response.data.message)
                emits('invisible');
                console.log(e);
            }
        } else {
            wasClick.value = true
        }
    } else {
        wasClick.value = true
    }
};
const exportCSV = async() => {
    if (form.fromMonth != '' && form.toMonth != '') {
        if (formatToPost(form.fromMonth, 'start') < formatToPost(form.toMonth, 'end')) {
            let api = `http://127.0.0.1:8000/api/get-timekeeping-export/${formatToPost(form.fromMonth, 'start')}/${formatToPost(form.toMonth, 'end')}/${prop.userId}`;
            if (router.currentRoute.value.fullPath === '/schedule') {
                api = `http://127.0.0.1:8000/api/get-timekeeping-export/${formatToPost(form.fromMonth, 'start')}/${formatToPost(form.toMonth, 'end')}/${user.id}`
            }
            try {
                await axios
                    .get(api, {
                        headers: { Authorization: `Bearer ${user.token}` },
                    })
                    .then(function (response) {
                        data.value = response.data.data;
                        const username = response.data.data[0].user;
                        const fromDate = new Date(form.fromMonth);
                        const toDate = new Date(form.toMonth);
                        const currentDate = new Date(fromDate);

                        const csvData = [];
                        const headerRow = [
                            'Date',
                            'Day of week',
                            'Time check in',
                            'Time check out',
                            'Shift',
                            'Status AM',
                            'Status PM',
                            'Time work',
                            'User name',
                        ];
                        csvData.push(headerRow);

                        while (currentDate <= toDate) {
                            const currentMonth = currentDate.getMonth() + 1;
                            const currentYear = currentDate.getFullYear();
                            const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
                            const daysArray = Array.from({ length: daysInMonth }, (_, i) => i + 1);

                            daysArray.forEach((day) => {
                                const dataForDay = data.value.find((item) => {
                                    const itemDate = new Date(item.date);
                                    return (
                                        itemDate.getMonth() + 1 === currentMonth &&
                                        itemDate.getDate() === day
                                    );
                                });
                                const rowData = [
                                    `${currentYear}-${currentMonth}-${day}`,
                                    getDayOfWeek(currentDate, day),
                                ];

                                if (dataForDay) {
                                    rowData.push(
                                        dataForDay['timeCheckIn'],
                                        dataForDay['timeCheckOut'],
                                        dataForDay['shift'],
                                        statusWork(dataForDay['status_AM']),
                                        statusWork(dataForDay['status_PM']),
                                        dataForDay['timeWork'],
                                        username
                                    );
                                } else {
                                    rowData.push(
                                        '00:00 (00:00)',
                                        '00:00 (00:00)',
                                        'OFF',
                                        'OFF',
                                        'OFF',
                                        '00:00',
                                        username
                                    );
                                }
                                csvData.push(rowData);
                            });

                            currentDate.setMonth(currentDate.getMonth() + 1);
                        }
                        const csvContent = Papa.unparse(csvData);
                        const csvBlob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                        const fileName = `data_${prop.mode}_${formatToPost(form.fromMonth)}-${formatToPost(form.toMonth)}.csv`;
                        FileSaver.saveAs(csvBlob, fileName);
                        messages('success', 'Export complete');
                        emits('invisible');[]
                    });
            } catch (e) {
                messages('error', e.response.data.message)
                emits('invisible');
                console.log(e);
            }
        } else {
            wasClick.value = true
        }
    } else {
        wasClick.value = true
    }
};
const getDayOfWeek = (date, day) => {
    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const currentYear = date.getFullYear();
    const currentMonth = date.getMonth();
    const currentDay = new Date(currentYear, currentMonth, day);
    const dayOfWeek = currentDay.getDay();
    return weekdays[dayOfWeek];
}
const statusWork = (status) => {
    if (status == 0) {
        return 'Work';
    } else if (status == 1) {
        return 'Remote'
    } else {
        return 'OFF'
    }
}
const formatToPost = (time, type) => {
    const date = new Date(time);
    const year = date.getFullYear();
    const month = (date.getMonth() + 1).toString().padStart(2, '0');
    const daysInMonth = new Date(year, month, 0).getDate();
    const day = date.getDate().toString().padStart(2, '0');
    if (type == 'start') {
        return `${year}-${month}-${day}`;
    } else {
        return `${year}-${month}-${daysInMonth}`;
    }
}
const messages = (type, message) => {
    alertStore.alert = true
    alertStore.type = type
    alertStore.msg = message
}
</script>