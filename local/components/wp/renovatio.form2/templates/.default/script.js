class RenovatioForm2 {

    #formSelector = '.js-renovatio-form2-form';

    #tabItemSelector = '.js-renovatio-form2-tab-item';

    #departmentItemSelector = '.js-renovatio-form2-department-item';
    #departmentEmptySelector = '.js-renovatio-form2-department-empty';
    #departmentValSelector = '.js-renovatio-form2-department-val';

    #doctorItemSelector = '.js-renovatio-form2-doctor-item';
    #doctorEmptySelector = '.js-renovatio-form2-doctor-empty';
    #doctorValSelector = '.js-renovatio-form2-doctor-val';

    #timeModalContentSelector = '[data-modal=renovatio-form2-modal-time] .js-renovatio-form2-modal-content';
    #timeItemClass = 'js-renovatio-form2-time-item';
    #timeItemSelector = '.' + this.#timeItemClass;
    #timeEmptySelector = '.js-renovatio-form2-time-empty';
    #timeValSelector = '.js-renovatio-form2-time-val';

    #fieldnameType = 'type';
    #fieldnameDepartment = 'department';
    #fieldnameDoctor = 'doctor';
    #fieldnameTime = 'time';
    #fieldnameName = 'name';
    #fieldnameLastname = 'last_name';
    #fieldnameSecondname = 'second_name';
    #fieldnameBirthdate = 'birthdate';
    #fieldnamePhone = 'phone';

    #needMoreData = false;

    #dadataToken = '888';
    #dadataBox = null;
    #dadataAbortControllerPull = [];

    constructor() {
        document.addEventListener('DOMContentLoaded', () => {
            this.#init();
        });
    }

    #init = () => {
        if (!document.querySelector(this.#formSelector)) {
            return;
        }
        this.#bindTabItem();
        this.#bindDepartmentItem();
        this.#bindDoctorItem();
        this.#bindForm();
        this.#bindDadata();
    }

    #getDadata = (part, query, success) => {

        const url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest/fio";
        const token = this.#dadataToken;

        const controller = new AbortController();
        this.#dadataAbortControllerPull.push(controller);

        const options = {
            method: "POST",
            mode: "cors",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": "Token " + token
            },
            body: JSON.stringify({
                count: 8,
                query: query,
                parts: [part],
            }),
            signal: controller.signal,
        }

        fetch(url, options)
            .then(response => response.json())
            .then(result => success(result))
            .catch(error => {
                if (error.name === 'AbortError') {
                    console.log('Запрос был отменён, это не ошибка.');
                } else {
                    console.error('Ошибка запроса:', error);
                }
            });
    }

    #showDadataBox = (res, currentTarget) => {
        let items = [];
        if (
            typeof res === 'object'
            && typeof res.suggestions === 'object'
        ) {
            res.suggestions.forEach((el) => {
                const item = document.createElement('div');
                item.classList.add('renovatio-form2-dadata-item');
                item.innerHTML = el.value;
                item.dataset.val = el.value;
                item.addEventListener('click', (e) => {
                    currentTarget.value = e.currentTarget.dataset.val;
                });
                items.push(item);
            });
        }
        if (!items.length) {
            return;
        }
        this.#clearDadataBox();
        const box = document.createElement('div');
        box.classList.add('renovatio-form2-dadata-box');
        items.forEach(el => box.append(el));
        document.body.append(box);
        this.#dadataBox = box;
        const rect = currentTarget.getBoundingClientRect();
        const elementL = rect.left + window.scrollX;
        const elementT = rect.top + window.scrollY + currentTarget.offsetHeight;
        box.style.left = elementL + 'px';
        box.style.top = elementT + 'px';
        box.style.width = currentTarget.offsetWidth + 'px';
    }

    #clearDadataBox = () => {
        if (this.#dadataBox !== null) {
            this.#dadataBox.remove();
            this.#dadataBox = null;
        }
    }

    #getField = (name) => {
        return document.querySelector(this.#formSelector + ' input[name="' + name + '"]');
    }

    #bindDadata = () => {

        const params = {
            [this.#fieldnameLastname]: {part: 'SURNAME'},
            [this.#fieldnameName]: {part: 'NAME'},
            [this.#fieldnameSecondname]: {part: 'PATRONYMIC'},
        };

        Object.entries(params).forEach((val) => {
            const key = val[0];
            const value = val[1];
            const el = this.#getField(key);
            el.addEventListener('input', (e) => {
                const currentTarget = e.currentTarget;
                const val = currentTarget.value.trim();
                this.#clearDadataBox();
                this.#dadataAbortControllerPull.forEach((el, i) => {
                    el.abort();
                    delete this.#dadataAbortControllerPull[i];
                });
                if (val !== '') {
                    this.#getDadata(value.part, val, (res) => {
                        this.#showDadataBox(res, currentTarget);
                    });
                    this.#bindDadataClose();
                }
            });
        });
    }

    #bindDadataClose = () => {
        if (document.renovatioForm2DadataCloseWinBinded) {
            return;
        }
        document.renovatioForm2DadataCloseWinBinded = true;
        document.addEventListener('click', this.#clearDadataBox);
        document.addEventListener('keydown', (evt) => {
            evt = evt || window.event;
            let isEscape = false;
            if ("key" in evt) {
                isEscape = (evt.key === "Escape" || evt.key === "Esc");
            } else {
                isEscape = (evt.keyCode === 27);
            }
            if (isEscape) {
                this.#clearDadataBox();
            }
        });
    }

    #hash = (val) => {
        let hash = 0, i, chr;
        if (val.length === 0) return hash;
        for (i = 0; i < val.length; i++) {
            chr = val.charCodeAt(i);
            hash = ((hash << 5) - hash) + chr;
            hash |= 0;
        }
        return hash;
    }

    #clearDepartment = () => {
        const valEl = document.querySelector(this.#departmentValSelector);
        document.querySelector(this.#departmentEmptySelector).style.display = '';
        valEl.innerHTML = '';
        document.querySelectorAll(this.#departmentItemSelector).forEach((el) => {
            el.querySelector('input[type="radio"]').checked = false;
        });
        const styleClass = 'js-style-' + this.#hash(this.#departmentValSelector);
        const nextEl = valEl.nextElementSibling;
        if (nextEl && nextEl.classList.contains(styleClass)) {
            nextEl.remove();
        }
        this.#getField(this.#fieldnameDepartment).value = '';
    }

    #clearDoctor = () => {
        document.querySelector(this.#doctorEmptySelector).style.display = '';
        document.querySelector(this.#doctorValSelector).innerHTML = '';
        document.querySelectorAll(this.#doctorItemSelector).forEach((el) => {
            el.querySelector('input[type="radio"]').checked = false;
        });
        this.#getField(this.#fieldnameDoctor).value = '';
    }

    #clearTime = () => {
        const timeModalContentEl = document.querySelector(this.#timeModalContentSelector);
        document.querySelector(this.#timeEmptySelector).style.display = '';
        document.querySelector(this.#timeValSelector).innerHTML = '';
        timeModalContentEl.innerHTML = timeModalContentEl.dataset.defTextNoDoctor;
        this.#getField(this.#fieldnameTime).value = '';
    }

    #bindTabItem = () => {
        document.querySelectorAll(this.#tabItemSelector).forEach((el) => {
            el.addEventListener('click', (e) => {
                this.#clearDepartment();
                this.#clearDoctor();
                this.#clearTime();
                this.#getField(this.#fieldnameType).value = e.currentTarget.dataset.code;
            });
        });
    }

    #checkForm = (data) => {
        const err = [];
        if (data[this.#fieldnameDoctor] === '') {
            err.push('Выберите врача');
        }
        if (data[this.#fieldnameTime] === '') {
            err.push('Выберите время');
        }
        if (data[this.#fieldnameName].trim() === '') {
            err.push('Укажите Ваше имя');
        }
        if (this.#needMoreData) {
            if (data[this.#fieldnameLastname].trim() === '') {
                err.push('Укажите фамилию');
            }
            if (data[this.#fieldnameSecondname].trim() === '') {
                err.push('Укажите отчество');
            }
            if (!/^\d\d\.\d\d\.\d\d\d\d$/.test(data[this.#fieldnameBirthdate])) {
                err.push('Ошибка в дате рождения');
            }
        }
        if (!/^\+7 \(\d\d\d\) \d\d\d-\d\d-\d\d$/.test(data[this.#fieldnamePhone])) {
            err.push('Ошибка в номере телефона');
        }
        return {
            err: err,
        };
    }

    #toggleMoreData = (display, clear = false) => {
        [this.#fieldnameLastname, this.#fieldnameSecondname, this.#fieldnameBirthdate].forEach((name) => {
            const el = this.#getField(name);
            el.style.display = display;
            if (clear) {
                el.value = '';
            }
        })
    }

    #bindForm = () => {
        document.querySelector(this.#formSelector).addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(e.currentTarget);
            const data = {};
            formData.forEach((val, key) => {
                data[key] = val;
            });
            const checkRes = this.#checkForm(data);
            if (checkRes.err.length) {
                alert(checkRes.err.join('\n'));
                return;
            }
            LcliPreloader.start(true);
            BX.ajax.runComponentAction(
                'wp:renovatio.form2',
                'submit',
                {mode: 'class', data: {data: data}}
            ).then((result) => {
                LcliPreloader.stop();
                this.#submitRes(result);
            }, this.#ajaxFail);
        });
    }

    #submitRes = (result) => {
        if (!(
            typeof result === 'object'
            && typeof result.data === 'object'
        )) {
            alert('Ошибка');
            return;
        }
        let isNoErr = false;
        const msg = [];
        if (typeof result.data.msg !== 'undefined') {
            result.data.msg.forEach(el => msg.push(el));
        }
        if (typeof result.data.needMoreData !== 'undefined' && !this.#needMoreData) {
            this.#toggleMoreData('');
            $(this.#getField(this.#fieldnameBirthdate)).inputmask({
                placeholder: 'дд.мм.гггг',
                mask: '99.99.9999',
            });
            this.#needMoreData = true;
        }
        if (typeof result.data.success !== 'undefined') {
            this.#successForm();
            isNoErr = true;
        }
        if (msg.length) {
            alert(msg.join('\n'));
            isNoErr = true;
        }
        if (isNoErr) {
            return;
        }
        alert('Ошибка');
    }

    #successForm = () => {

        const formEl = document.querySelector(this.#formSelector);

        formEl.reset();
        this.#getField(this.#fieldnamePhone).value = '';
        this.#clearDepartment();
        this.#clearDoctor();
        this.#clearTime();
        this.#toggleMoreData('none', true);
        this.#needMoreData = false;

        if (!formEl.renovatioForm2RemoveSuccessBinded) {
            formEl.renovatioForm2RemoveSuccessBinded = true;
            document.addEventListener('click', () => {
                delete formEl.dataset.success;
            });
        }
        formEl.dataset.success = 'true';
        setTimeout(() => {
            delete formEl.dataset.success;
        }, 3500);
    }

    #bindDepartmentItem = () => {
        document.querySelectorAll(this.#departmentItemSelector).forEach((el) => {
            el.addEventListener('click', (e) => {
                this.#clearDepartment();
                this.#clearDoctor();
                this.#clearTime();
                const valEl = document.querySelector(this.#departmentValSelector);
                e.currentTarget.querySelector('input[type="radio"]').checked = true;
                document.querySelector(this.#departmentEmptySelector).style.display = 'none';
                valEl.innerHTML = e.currentTarget.dataset.valText;
                const styleClass = 'js-style-' + this.#hash(this.#departmentValSelector);
                valEl.insertAdjacentHTML('afterend',
                    '<style class="' + styleClass + '">'
                    + this.#doctorItemSelector
                    + ':not([data-departments*="|' + e.currentTarget.dataset.code + '|"])'
                    + '{display: none;}</style>');
                this.#getField(this.#fieldnameDepartment).value = e.currentTarget.dataset.code;
            });
        });
    }

    #bindDoctorItem = () => {
        document.querySelectorAll(this.#doctorItemSelector).forEach((el) => {
            el.addEventListener('click', (e) => {
                const currentTarget = e.currentTarget;
                if (currentTarget.renovatioForm2ClickLock) {
                    return;
                }
                currentTarget.renovatioForm2ClickLock = true;
                this.#clearDoctor();
                this.#clearTime();
                currentTarget.querySelector('input[type="radio"]').checked = true;
                document.querySelector(this.#doctorEmptySelector).style.display = 'none';
                document.querySelector(this.#doctorValSelector).innerHTML = currentTarget.dataset.valText;
                this.#getField(this.#fieldnameDoctor).value = currentTarget.dataset.id;
                const unlock = () => {
                    delete currentTarget.renovatioForm2ClickLock;
                };
                if (!window.renovatioForm2CatchAjaxErrBinded) {
                    window.renovatioForm2CatchAjaxErrBinded = true;
                    window.addEventListener('error', (e) => {
                        if (e.message === "Uncaught TypeError: Cannot read properties of undefined (reading 'xhr')") {
                            unlock();
                        }
                    });
                }
                this.#loadTime(currentTarget.dataset.id, unlock);
            });
        });
    }

    #bindTimeItem = () => {
        document.querySelectorAll(this.#timeItemSelector).forEach((el) => {
            el.addEventListener('click', (e) => {
                e.currentTarget.querySelector('input[type="radio"]').checked = true;
                document.querySelector(this.#timeEmptySelector).style.display = 'none';
                document.querySelector(this.#timeValSelector).innerHTML = e.currentTarget.dataset.resText;
                this.#getField(this.#fieldnameTime).value = JSON.stringify({
                    clinicId: e.currentTarget.dataset.clinicId,
                    date: e.currentTarget.dataset.date,
                    start: e.currentTarget.dataset.start,
                    end: e.currentTarget.dataset.end,
                });
            });
        });
    }

    #timeItemTpl = (clinicId, date, timeStart, timeEnd) => {
        const itemId = this.#hash(clinicId + date + timeStart + timeEnd);
        const resText = date + ' в ' + timeStart;
        return '<div\n' +
            '    class="option-wrp ' + this.#timeItemClass + '"\n' +
            '    data-clinic-id="' + clinicId + '"\n' +
            '    data-date="' + date + '"\n' +
            '    data-start="' + timeStart + '"\n' +
            '    data-end="' + timeEnd + '"\n' +
            '    data-res-text="' + resText + '"\n' +
            '>\n' +
            '    <input type="radio" id="renovatio-form2-option-time-' + itemId + '">\n' +
            '    <label class="option" for="renovatio-form2-option-time-' + itemId + '">\n' +
            '        ' + resText + '\n' +
            '    </label>\n' +
            '</div>';
    }

    #ajaxFail = (success) => {
        LcliPreloader.stop();
        alert('Произошла ошибка, попробуйте повторить попытку');
        if (typeof success === 'function') {
            success();
        }
    }

    #loadTime = (id, success) => {
        LcliPreloader.start(true);
        BX.ajax.runComponentAction(
            'wp:renovatio.form2',
            'getTime',
            {mode: 'class', data: {
                data: {id: id},
            }}
        ).then((result) => {
            LcliPreloader.stop();
            if (
                typeof result === 'object'
                && typeof result.data === 'object'
                && typeof result.data.res === 'object'
            ) {
                let modalContentHtml = '';
                result.data.res.forEach((el) => {
                    const item = this.#timeItemTpl(
                        el.clinic_id,
                        el.date,
                        el.time_start_short,
                        el.time_end_short
                    );
                    modalContentHtml += item;
                });
                if (modalContentHtml === '') {
                    const timeModalContentEl = document.querySelector(this.#timeModalContentSelector);
                    modalContentHtml = timeModalContentEl.dataset.defTextNoTime;
                }
                document.querySelector(this.#timeModalContentSelector).innerHTML = modalContentHtml;
                this.#bindTimeItem();
            }
            success();
        }, () => {
            this.#ajaxFail(success);
        });
    }
}

new RenovatioForm2();