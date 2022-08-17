define(['jquery', 'underscore', 'twigjs'], function ($, _, Twig) {
  var CustomWidget = function () {
    var self = this;

    this.callbacks = {
      render: function () 
      {
        console.log('render');

        // если открыли карточку сделки
        if (self.system().area == 'lcard') 
        {
          let $widgets_block = $('#widgets_block');

          // если кнопки ещё нет
          if ($widgets_block.find('#show_products_button').length == 0) 
          {
              // путь к файлу стилей виджета
              let stylePath = self.params.path + '/style.css';
              // подключаю стили
              $('head').append('<link href="' + stylePath + '" rel="stylesheet">');
              
              $widgets_block.append(
                  self.render({ref: '/tmpl/controls/button.twig'}, {
                      id: 'show_products_button',
                      text: 'Посмотреть товары'
                  })
              );
          }
        }

        // таблица с товарами
        self.productsTable = '';

        // строки таблицы с товарами
        let productRows = '';
        // получаю товары со своего сервера
        $.getJSON('https://www.adelaida.ua/wApi.php', 
        {
          'action':'get_orders',
          'lead_id': AMOCRM.data.current_card.id                
        },
        
        function (products) 
        {
            if (products.length === 0) 
            {
                // если товаров нет
                productRows += '<tr><td colspan="2">Товаров нет</td></tr>';
            } 
            else 
            {
                // если есть товары, добавляю их в таблицу
                for (let product of products) 
                {
                    productRows += '<tr><td>' + product.name + '</td><td>' + product.quantity + '</td></tr>';
                }

                // заголовок таблицы с товарами
                let thead = '<thead><tr><th>Название</th><th>Количество</th></tr></thead>';
                // строки таблицы с товарами
                let tbody = '<tbody>' + productRows + '</tbody>';
                // таблица с товарами
                self.productsTable = '<table>' + thead + tbody + '</table>';
            }
        });

        return true;
       
      },
      
      
      
      
      
      
      
      init: _.bind(function () {
        console.log('init');

        AMOCRM.addNotificationCallback(self.get_settings().widget_code, function (data) {
          console.log(data)
        });

        this.add_action("phone", function (params) {
          /**
           * код взаимодействия с виджетом телефонии
           */
          console.log(params)
        });

        this.add_source("sms", function (params) {
          /**
           params - это объект в котором будут  необходимые параметры для отправки смс

           {
             "phone": 75555555555,   // телефон получателя
             "message": "sms text",  // сообщение для отправки
             "contact_id": 12345     // идентификатор контакта, к которому привязан номер телефона
          }
           */

          return new Promise(_.bind(function (resolve, reject) {
              // тут будет описываться логика для отправки смс
              self.crm_post(
                'https://example.com/',
                params,
                function (msg) {
                  console.log(msg);
                  resolve();
                },
                'text'
              );
            }, this)
          );
        });

        return true;
      }, this),
      bind_actions: function () {
        console.log('bind_actions');
        return true;
      },
      settings: function () {
        return true;
      },
      onSave: function () {
        alert('click');
        return true;
      },
      destroy: function () {

      },
      contacts: {
        //select contacts in list and clicked on widget name
        selected: function () {
          console.log('contacts');
        }
      },
      leads: {
        //select leads in list and clicked on widget name
        selected: function () {
          console.log('leads');
        }
      },
      tasks: {
        //select taks in list and clicked on widget name
        selected: function () {
          console.log('tasks');
        }
      },
      advancedSettings: _.bind(function () {
        var $work_area = $('#work-area-' + self.get_settings().widget_code),
          $save_button = $(
            Twig({ref: '/tmpl/controls/button.twig'}).render({
              text: 'Сохранить',
              class_name: 'button-input_blue button-input-disabled js-button-save-' + self.get_settings().widget_code,
              additional_data: ''
            })
          ),
          $cancel_button = $(
            Twig({ref: '/tmpl/controls/cancel_button.twig'}).render({
              text: 'Отмена',
              class_name: 'button-input-disabled js-button-cancel-' + self.get_settings().widget_code,
              additional_data: ''
            })
          );

        console.log('advancedSettings');

        $save_button.prop('disabled', true);
        $('.content__top__preset').css({float: 'left'});

        $('.list__body-right__top').css({display: 'block'})
          .append('<div class="list__body-right__top__buttons"></div>');
        $('.list__body-right__top__buttons').css({float: 'right'})
          .append($cancel_button)
          .append($save_button);

        self.getTemplate('advanced_settings', {}, function (template) {
          var $page = $(
            template.render({title: self.i18n('advanced').title, widget_code: self.get_settings().widget_code})
          );

          $work_area.append($page);
        });
      }, self),

      /**
       * Метод срабатывает, когда пользователь в конструкторе Salesbot размещает один из хендлеров виджета.
       * Мы должны вернуть JSON код salesbot'а
       *
       * @param handler_code - Код хендлера, который мы предоставляем. Описан в manifest.json, в примере равен handler_code
       * @param params - Передаются настройки виджета. Формат такой:
       * {
       *   button_title: "TEST",
       *   button_caption: "TEST",
       *   text: "{{lead.cf.10929}}",
       *   number: "{{lead.price}}",
       *   url: "{{contact.cf.10368}}"
       * }
       *
       * @return {{}}
       */
      onSalesbotDesignerSave: function (handler_code, params) {
        var salesbot_source = {
            question: [],
            require: []
          },
          button_caption = params.button_caption || "",
          button_title = params.button_title || "",
          text = params.text || "",
          number = params.number || 0,
          handler_template = {
            handler: "show",
            params: {
              type: "buttons",
              value: text + ' ' + number,
              buttons: [
                button_title + ' ' + button_caption,
              ]
            }
          };

        console.log(params);

        salesbot_source.question.push(handler_template);

        return JSON.stringify([salesbot_source]);
      },
    };
    return this;
  };

  return CustomWidget;
});