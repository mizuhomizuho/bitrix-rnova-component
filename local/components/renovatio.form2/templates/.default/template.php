<?php
$defOnline = true;
?>

<section class="section booking-section renovatio-form2-box">
    <div class="container">

        <h2 class="section__title">Записаться на прием</h2>
        <p class="section__subtitle">Оставьте свои контактные данные <br> и мы свяжемся с вами</p>

        <div class="booking-form">
            <ul class="nav nav-pills mb-3" role="tablist">
                <li class="nav-item js-renovatio-form2-tab-item <?=$defOnline ? ' active ' : ''?>" data-code="ONLINE_RECEPTION">
                    <div class="nav-item-inner"></div>
                    <a class="nav-link <?=$defOnline ? ' active ' : ''?>" data-toggle="pill" href="#" role="tab" aria-controls="pills-profile" aria-selected="true">
                        <div class="decoration"></div>
                        <img src="<?=SITE_TEMPLATE_PATH?>/images/1.svg" alt="">
                        Онлайн прием
                    </a>
                </li>
                <li class="nav-item js-renovatio-form2-tab-item <?=$defOnline ? '' : ' active '?>" data-code="FACE_TO_FACE_RECEPTION">
                    <div class="nav-item-inner"></div>
                    <a class="nav-link <?=$defOnline ? '' : ' active '?>" data-toggle="pill" href="#" role="tab" aria-controls="pills-real-visit" aria-selected="false">
                        <div class="decoration"></div>
                        <img src="<?=SITE_TEMPLATE_PATH?>/images/2.svg" alt="">
                        Очный прием
                    </a>
                </li>
            </ul>

            <div class="tab-content">

                <div class="tab-pane active offline-container" id="pills-real-visit" role="tabpanel"
                     aria-labelledby="pills-real-visit-tab">
                    
                    <form class="js-renovatio-form2-form renovatio-form2-form">

                        <input type="hidden" name="type" value="<?=$defOnline ? 'ONLINE_RECEPTION' : 'FACE_TO_FACE_RECEPTION'?>">
                        <input type="hidden" name="department">
                        <input type="hidden" name="doctor">
                        <input type="hidden" name="time">
                        
                        <div class="select-custom-run" data-modal="renovatio-form2-modal-department">
                            <div class="custom-select-decoration-input-form2">
                                <div class="js-renovatio-form2-department-empty">
                                    Выберите отделение
                                </div>
                                <div class="js-renovatio-form2-department-val"></div>
                            </div>
                        </div>
                        <div class="select-custom-run" data-modal="renovatio-form2-modal-doctor">
                            <div class="custom-select-decoration-input-form2">
                                <div class="js-renovatio-form2-doctor-empty">
                                    Выберите врача*
                                </div>
                                <div class="js-renovatio-form2-doctor-val"></div>
                            </div>
                        </div>
                        <div class="select-custom-run" data-modal="renovatio-form2-modal-time">
                            <div class="custom-select-decoration-input-form2">
                                <div class="js-renovatio-form2-time-empty">
                                    Время*
                                </div>
                                <div class="js-renovatio-form2-time-val"></div>
                            </div>
                        </div>
                        <input class="input" type="text" placeholder="Имя*" name="name">
                        <input style="display: none" class="input" type="text" placeholder="Фамилия*" name="last_name">
                        <input style="display: none" class="input" type="text" placeholder="Отчество*" name="second_name">
                        <input style="display: none" class="input" type="text" placeholder="Дата рождения*" name="birthdate">
                        <input class="input" type="tel" placeholder="Телефон*" name="phone">
                        <input id="pre-section-socials" class="btn btn_blue" type="submit" value="Записаться">
                        <div class="privacy_notice">
                            Заполняя форму, я принимаю <a href="/agreement.php">условия передачи
                                информации</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>

<?php

$modals = [[
    'id' => 'renovatio-form2-modal-department',
    'content' => function () use ($arResult): void {
        ?>
        <div class="content">
        <?php
        foreach ($arResult['DATA']['DEPARTMENTS'] as $department) {
            ?>
            <div
                class="option-wrp js-renovatio-form2-department-item"
                data-val-text="<?=htmlspecialchars($department['NAME'])?>"
                data-code="<?=htmlspecialchars($department['CODE'])?>"
                data-types="|<?=htmlspecialchars(implode('|', array_keys($department['TYPES'])))?>|"
            >
                <input type="radio" id="renovatio-form2-option-department-<?=htmlspecialchars($department['CODE'])?>">
                <label class="option" for="renovatio-form2-option-department-<?=htmlspecialchars($department['CODE'])?>">
                    <?=htmlspecialchars($department['NAME'])?>
                </label>
            </div>
            <?php
        }
        ?>
        </div>
        <?php
    },
], [
    'id' => 'renovatio-form2-modal-doctor',
    'content' => function () use ($arResult): void {
        ?>
        <div class="content">
        <?php
        foreach ($arResult['DATA']['DOCTORS'] as $doctor) {
            ?>
            <div
                class="option-wrp js-renovatio-form2-doctor-item"
                data-id="<?=$doctor['RENOVATIO_ID']?>"
                data-val-text="<?=htmlspecialchars($doctor['NAME'])?>"
                data-departments="|<?=htmlspecialchars(implode('|', array_keys($arResult['DATA']["DOCTORS_TYPES"][$doctor['ID']]['DEPARTMENT'])))?>|"
                data-types="|<?=implode('|', array_keys($arResult['DATA']["DOCTORS_TYPES"][$doctor['ID']]['TYPE']))?>|"
            >
                <input type="radio" id="renovatio-form2-option-doctor-<?=$doctor['ID']?>">
                <label class="option" for="renovatio-form2-option-doctor-<?=$doctor['ID']?>">
                    <?=htmlspecialchars($doctor['NAME'])?>
                </label>
            </div>
            <?php
        }
        ?>
        </div>
        <?php
    },
], [
    'id' => 'renovatio-form2-modal-time',
    'content' => function (): void {
        $timeDefTextNoDoctor
            = '<div class=renovatio-form2-modal-time-def-text>Вы не выбрали врача</div>';
        $timeDefTextNoTime
            = '<div class=renovatio-form2-modal-time-def-text>Нет свободного времени</div>';
        ?>
        <div
            class="content js-renovatio-form2-modal-content"
            data-def-text-no-doctor="<?= $timeDefTextNoDoctor ?>"
            data-def-text-no-time="<?= $timeDefTextNoTime ?>"
        >
            <?= $timeDefTextNoDoctor ?>
        </div>
        <?php
    },
],];

$this->SetViewTarget('footer');
foreach ($modals as $modal) {
    ?>
    <div class="popup-overlay" data-modal="<?=$modal['id']?>" style="display: none;">
        <div class="popup">
            <div class="close">
                <svg viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">
                    <g transform="translate(3.9661 3.5678)">
                        <path d="m-2.5783e-4 -0.0014681 17.436 18.214" fill="#5f6368"
                              stroke="#5f6368" stroke-linecap="round" stroke-width="3.2316"></path>
                        <path d="m-2.5783e-4 18.212 17.436-18.214" fill="#5f6368" stroke="#5f6368"
                              stroke-linecap="round" stroke-width="3.2316"></path>
                    </g>
                </svg>
            </div>
            <div class="container">
                <?php
                $modal['content']();
                ?>
            </div>
        </div>
    </div>
    <?php
}
$this->EndViewTarget();
