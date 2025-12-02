Лабораторная работа №6. Балансирование нагрузки в облаке и авто-масштабирование

Цель работы
Закрепить навыки работы с AWS EC2, Elastic Load Balancer, Auto Scaling и CloudWatch, создав отказоустойчивую и автоматически масштабируемую архитектуру.

Я развернула:

VPC с публичными и приватными подсетями;
Виртуальную машину с веб-сервером (nginx);
Application Load Balancer;
Auto Scaling Group (на основе AMI);
нагрузочный тест с использованием CloudWatch.
Условие
Для студентов специализации DevOps. Для получения высшей оценки рекомендуется дополнительно автоматизировать процесс развертывания VPC и виртуальных машин с помощью Terraform.

Шаг 1. Создание VPC и подсетей
Создайте VPC (если уже есть — используйте существующую):
Создайте 2 публичные подсети и 2 приватные подсети в разных зонах доступности (например, us-east-1a и us-east-1b):
CIDR-блок: 10.0.1.0/24 и 10.0.2.0/24
Создайте Internet Gateway и прикрепите его к VPC.
В Route Table пропишите маршрут для публичных подсетей:
Destination: 0.0.0.0/0 → Target: Internet Gateway
Рекомендуется использовать мастер-настройки (wizard) при создании VPC.

Шаг 2. Создание и настройка виртуальной машины
Запусите виртуальную машину в созданной подсети:

AMI: Amazon Linux 2
Тип: t3.micro
В настройках сети выберите созданную VPC и подсеть.
Не забудьте назначить публичный IP-адрес (Enable auto-assign public IP).
В настройках безопасности создайте новую группу безопасности с правилами:
Входящие правила:

SSH (порт 22) — источник: ваш IP
HTTP (порт 80) — источник: 0.0.0.0/0
Исходящие правила:

Все трафики — источник: 0.0.0.0/0
В Advanced Details -> Detailed CloudWatch monitoring выберите Enable. Это позволит собирать дополнительные метрики для Auto Scaling.

В настройках UserData укажите следующий скрипт init.sh, который установит, запустит nginx.

Дождитесь, пока Status Checks виртуальной машины станут зелёными (3/3 checks passed).

Убедитесь, что веб-сервер работает, подключившись к публичному IP-адресу виртуальной машины через браузер (развертывание сервера может занять до 5 минут).
Шаг 3. Создание AMI
В EC2 выберите Instance → Actions → Image and templates → Create image.
Назовите AMI, например: project-web-server-ami.
Дождитесь появления AMI в разделе AMIs.
Что такое image и чем он отличается от snapshot? Какие есть варианты использования AMI?

Шаг 4. Создание Launch Template
На основе Launch Template в дальнейшем будет создаваться Auto Scaling Group, то есть подниматься новые инстансы по шаблону.

В разделе EC2 выберите Launch Templates → Create launch template.
Укажите следующие параметры:
Название: project-launch-template
AMI: выберите созданную ранее AMI (My AMIs -> project-web-server-ami).
Тип инстанса: t3.micro.
Security groups: выберите ту же группу безопасности, что и для виртуальной машины.
Нажмите Create launch template.
В разделе Advanced details -> Detailed CloudWatch monitoring выберите Enable. Это позволит собирать дополнительные метрики для Auto Scaling.
Что такое Launch Template и зачем он нужен? Чем он отличается от Launch Configuration?

Шаг 5. Создание Target Group
В разделе EC2 выберите Target Groups → Create target group.
Укажите следующие параметры:

Название: project-target-group
Тип: Instances
Протокол: HTTP
Порт: 80
VPC: выберите созданную VPC
Нажмите Next -> Next, затем Create target group.

Зачем необходим и какую роль выполняет Target Group?

Шаг 6. Создание Application Load Balancer
В разделе EC2 выберите Load Balancers → Create Load Balancer → Application Load Balancer.
Укажите следующие параметры:

Название: project-alb
Scheme: Internet-facing.

В чем разница между Internet-facing и Internal?

Subnets: выберите созданные 2 публичные подсети.

Security Groups: выберите ту же группу безопасности, что и для виртуальной машины.
Listener: протокол HTTP, порт 80.
Default action: выберите созданную Target Group project-target-group.

Что такое Default action и какие есть типы Default action?

Нажмите Create load balancer.

Перейдите в раздел Resource map и убедитесь что существуют связи между Listeners, Rules и Target groups.
Шаг 7. Создание Auto Scaling Group
В разделе EC2 выберите Auto Scaling Groups → Create Auto Scaling group.
Укажите следующие параметры:

Название: project-auto-scaling-group
Launch template: выберите созданный ранее Launch Template (project-launch-template).
Перейдите в раздел Choose instance launch options.
В разделеNetwork: выберите созданную VPC и две приватные подсети.

Почему для Auto Scaling Group выбираются приватные подсети?

Availability Zone distribution: выберите Balanced best effort.

Зачем нужна настройка: Availability Zone distribution?

Перейдите в раздел Integrate with other services и выберите Attach to an existing load balancer, затем выберите созданную Target Group (project-target-group).

Таким образом мы добавляем AutoScaling Group в Target Group нашего Load Balancer-а.
Перейдите в раздел Configure group size and scaling и укажите:
Минимальное количество инстансов: 2
Максимальное количество инстансов: 4
Желаемое количество инстансов: 2
Укажите Target tracking scaling policy и настройте масштабирование по CPU (Average CPU utilization — 50% / Instance warm-up period — 60 seconds).

Что такое Instance warm-up period и зачем он нужен?

В разделе Additional settings поставьте галочку на Enable group metrics collection within CloudWatch, чтобы собирать метрики Auto Scaling Group в CloudWatch. Этот пункт позволит нам отслеживать состояние группы и её производительность.

Перейдите в раздел Review и нажмите Create Auto Scaling group.
Шаг 8. Тестирование Application Load Balancer
Перейдите в раздел EC2 -> Load Balancers, выберите созданный Load Balancer и скопируйте его DNS-имя.
Вставьте DNS-имя в браузер и убедитесь, что вы видите страницу веб-сервера.
Обновите страницу несколько раз и посмотрите на IP-адреса в ответах.

Какие IP-адреса вы видите и почему?

Шаг 9. Тестирование Auto Scaling
Перейдите в CloudWatch -> Alarms, у вас должны быть созданы автоматические оповещения для Auto Scaling Group.
Выберите одно из оповещений (например, TargetTracking-XX-AlarmHigh-...), откройте и посмотрите на график CPU Utilization. На данный момент график должен быть низким (около 0-1%).
Перейдите в браузер и откройте 6-7 вкладок со следующим адресом:

http://<DNS-имя вашего Load Balancer-а>/load?seconds=60
В качестве альтернативы используйте скрипт curl.sh, указав в нём DNS-имя вашего Load Balancer-а.

Для специализации DevOps необходимо модифицировать скрипт curl.sh так, чтобы он:

принимал параметры из командной строки: количество потоков и длительность нагрузки.
(дополнительно) использовал ab (Apache Benchmark) или hey для создания нагрузки вместо бесконечного цикла с curl.
Вернитесь в CloudWatch и посмотрите на график CPU Utilization. Через несколько минут вы должны увидеть рост нагрузки.

Подождите 2-3 минуты, пока CloudWatch не зафиксирует высокую нагрузку и не создаст Alarm (будет показано красным цветом).
Перейдите в раздел EC2 -> Instances и посмотрите на количество запущенных инстансов.

Какую роль в этом процессе сыграл Auto Scaling?

Шаг 10. Завершение работы и очистка ресурсов
Остановите нагрузочный тест (закройте вкладки браузера или остановите скрипт curl.sh).
Перейдите в раздел EC2 -> Load Balancers, выберите созданный Load Balancer и удалите его (Delete).
Перейдите в раздел EC2 -> Target Groups, выберите созданную Target Group и удалите её (Delete).
Перейдите в раздел EC2 -> Auto Scaling Groups, выберите созданную группу и удалите её (Delete).
Перейдите в раздел EC2 -> Instances, выберите все запущенные инстансы и завершите их (Terminate).
Перейдите в раздел EC2 -> AMIs, выберите созданную AMI и удалите её (Deregister), при удалении выберите удаление связанных снимков (snapshots).
Перейдите в раздел EC2 -> Launch Templates, выберите созданный Launch Template и удалите его (Delete).
Перейдите в раздел VPC и удалите созданные VPC и подсети.