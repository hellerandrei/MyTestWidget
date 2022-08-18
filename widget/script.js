define(['jquery', 'underscore', 'twigjs', 'lib/components/base/modal'], function ($, _, Twig, Modal) 
{
  var CustomWidget = function () 
  {
    var self = this;
    // здесь будет кнопка, после отрисовки
    var button = $('#show_orders_button');

    // Загрузка товаров из внешнего источника
    function getOrdersData()
    {
        var orderRows = '';
        var action    = 'get_orders';
        var lead_id   = '';

        $.getJSON('https://www.adelaida.ua/wApi.php', 
        {
          'action':'get_orders',
          'lead_id': AMOCRM.data.current_card.id                
        },
        
        // Получение данных
        function (data) 
        {
            // Небольшая проверка на корректность и наличие товаров
            if ( data.status != 'success' || data.orders.length === 0 || data.action != action ) 
            {               
                orderRows += '<tr><td colspan="2">Товаров нет</td></tr>';
            } 
            else 
            // Заполняем таблицу товаров
            { 
              for (var order of data.orders) 
              {
                orderRows += '<tr><td>' + order.name + '</td><td>' + order.quantity + '</td></tr>';
              }
              
              var thead = '<thead><tr><th>Название</th><th>Количество</th></tr></thead>';                
              var tbody = '<tbody>' + orderRows + '</tbody>';
              
              self.ordersTable = '<table>' + thead + tbody + '</table>';
            }
            // Для визуальной проверки, чьи данные к нам пришли.
            lead_id = data.lead_id;
            console.log('lead_id = '+ lead_id);
            
            // Показываем результат
            ShowModal();

            // Выключаем прогрессбар на кнопке
            button.trigger('button:load:stop');
        });  
        
        
        return lead_id;      
    }

    // Функция отображает модальное окно с таблицей товаров
    function ShowModal()
    {
      var modal = new Modal(
      {
        class_name: 'orders-modal-window',
        init: function ($modal_body) 
        {
            $modal_body
                .trigger('modal:loaded') 
                .html(self.ordersTable);  
        },
        destroy: function () 
        {
        }
      });
      return true;
    }



    this.callbacks = {
      // Отрисовка
      render: function () 
      {
        console.log('render');
       
        // Проверяем, свое местонахождения
        if (self.system().area == 'lcard') 
        {
          let $widgets_block = $('#widgets_block');
         
          // Если кнопки еще нет
          if ($widgets_block.find('#show_orders_button').length == 0) 
          {              
              let stylePath = self.params.path + '/style.css';             
              $('head').append('<link href="' + stylePath + '" rel="stylesheet">');
              
              // добавляем кнопку https://storybook.amocrm.ru/?path=/docs/контролы-button--default-story
              $widgets_block.append(
                  self.render({ref: '/tmpl/controls/button.twig'}, {
                      id: 'show_orders_button',
                      text: 'Посмотреть товары'
                  })
              );
              // Помещаем элемент в переменную
              button = $('#show_orders_button');
          }
        }        
        self.ordersTable = '';        
        return true;       
      }, 
      
      init: _.bind(function () 
      {
        console.log('init');
        return true;
      }, this),
      
      // Обработка событий
      bind_actions: function () 
      {
        console.log('bind_actions');
        
        // Нажатие на кнопку показа товаров
        $('#widgets_block #show_orders_button').on('click', function () 
        {     
          // Стартуем прогресбар, завершается в асинхроне
          button.trigger('button:load:start');          
          try
          {
            getOrdersData();            
          }
          catch{}         
        });

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
      }
      
    };
    return this;
  };

  return CustomWidget;
});