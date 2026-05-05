// Инициализация частиц
function initParticles() {
    const container = document.getElementById('particles');
    const particleCount = 30;
    
    for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.className = 'particle';
        
        // Случайные параметры
        const size = Math.random() * 3 + 1;
        const posX = Math.random() * 100;
        const posY = Math.random() * 100;
        const duration = Math.random() * 20 + 10;
        const delay = Math.random() * 5;
        
        // Стили
        particle.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: rgba(255, 215, 0, ${Math.random() * 0.3 + 0.1});
            border-radius: 50%;
            left: ${posX}%;
            top: ${posY}%;
            animation: floatParticle ${duration}s linear infinite ${delay}s;
        `;
        
        container.appendChild(particle);
    }
    
    // Добавляем стили для анимации
    const style = document.createElement('style');
    style.textContent = `
        @keyframes floatParticle {
            0% { transform: translateY(0) translateX(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) translateX(${Math.random() * 100 - 50}px); opacity: 0; }
        }
        .particle { filter: blur(${Math.random() * 2}px); }
    `;
    document.head.appendChild(style);
}

// Анимация счетчиков
function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    const speed = 200; // Чем выше число, тем медленнее анимация
    
    counters.forEach(counter => {
        // Получаем целевое значение из data-count
        const target = parseInt(counter.getAttribute('data-count'));
        if (isNaN(target)) return;
        
        let current = 0;
        const increment = target / speed;
        
        const updateCount = () => {
            current += increment;
            
            if (current < target) {
                // Показываем целое число без плюса
                counter.innerText = Math.floor(current);
                requestAnimationFrame(updateCount);
            } else {
                // Финальное значение без плюса
                counter.innerText = target;
            }
        };
        
        updateCount();
    });
}
// Эффект параллакса
function initParallax() {
    const background = document.querySelector('.cyber-background');
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const rate = scrolled * -0.5;
        background.style.transform = `translate3d(0px, ${rate}px, 0px)`;
    });
}

// Reveal animations при скролле
function initScrollReveal() {
    const revealElements = document.querySelectorAll('.scroll-reveal');
    
    const revealOnScroll = () => {
        revealElements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const elementVisible = 150;
            
            if (elementTop < window.innerHeight - elementVisible) {
                element.classList.add('visible');
            }
        });
    };
    
    window.addEventListener('scroll', revealOnScroll);
    revealOnScroll(); // Проверяем сразу
}

// Мобильное меню
function initMobileMenu() {
    const menuBtn = document.getElementById('menuToggle');
    const navMenu = document.querySelector('.nav-hologram');
    
    if (menuBtn && navMenu) {
        menuBtn.addEventListener('click', () => {
            menuBtn.classList.toggle('active');
            navMenu.classList.toggle('active');
            
            // Анимация кнопки гамбургера
            const spans = menuBtn.querySelectorAll('span');
            if (menuBtn.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
        
        // Закрытие меню при клике на ссылку
        const navLinks = navMenu.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                menuBtn.classList.remove('active');
                navMenu.classList.remove('active');
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            });
        });
    }
}

// Интерактивные карточки
function initInteractiveCards() {
    const cards = document.querySelectorAll('.cyber-card');
    
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            const centerX = rect.width / 2;
            const centerY = rect.height / 2;
            
            const rotateY = (x - centerX) / 25;
            const rotateX = (centerY - y) / 80;
            
            card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) translateY(-5px)`;
            
            // Эффект свечения
            const glow = document.createElement('div');
            glow.style.cssText = `
                position: absolute;
                top: ${y}px;
                left: ${x}px;
                width: 100px;
                height: 100px;
                background: radial-gradient(circle, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
                border-radius: 50%;
                transform: translate(-50%, -50%);
                pointer-events: none;
                z-index: 1;
            `;
            card.appendChild(glow);
            
            // Удаляем через 300ms
            setTimeout(() => {
                if (glow.parentNode === card) {
                    card.removeChild(glow);
                }
            }, 300);
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        });
    });
}

// Эффект ввода текста для форм
function initFormEffects() {
    const inputs = document.querySelectorAll('.cyber-input, .cyber-select');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (!this.value) {
                this.parentElement.classList.remove('focused');
            }
        });
    });
}

// Загрузка специалистов (симуляция)
function loadSpecialists() {
    const container = document.getElementById('specialists-container');
    
    // Пример данных (в реальности будет AJAX запрос)
    const specialists = [
        {
            name: "ИВАН ПЕТРОВ",
            level: "JUNIOR",
            tags: ["REACT", "JS", "HTML/CSS"],
            bio: "Фронтенд-разработчик с фокусом на современные фреймворки",
            skills: 75
        },
        {
            name: "АННА СМИРНОВА",
            level: "MIDDLE",
            tags: ["PYTHON", "DJANGO", "POSTGRESQL"],
            bio: "Backend-разработчик с опытом создания масштабируемых API",
            skills: 85
        },
        {
            name: "ДМИТРИЙ ИВАНОВ",
            level: "JUNIOR",
            tags: ["VUE", "NODE.JS", "MONGODB"],
            bio: "Fullstack разработчик, специализируется на современных стеках",
            skills: 70
        },
        {
            name: "ЕЛЕНА КОЗЛОВА",
            level: "MIDDLE",
            tags: ["UI/UX", "FIGMA", "ADOBE XD"],
            bio: "Дизайнер интерфейсов с глубоким пониманием пользовательского опыта",
            skills: 90
        }
    ];
    
    // Очищаем контейнер
    container.innerHTML = '';
    
    // Создаем карточки
    specialists.forEach(spec => {
        const card = document.createElement('div');
        card.className = 'specialist-card cyber-card scroll-reveal';
        
        card.innerHTML = `
            <div class="card-glitch"></div>
            <div class="specialist-avatar">
                <div class="avatar-glow"></div>
                <img src="assets/images/specialists/default.jpg" alt="${spec.name}">
            </div>
            <div class="specialist-info">
                <h3 class="cyber-name">${spec.name}</h3>
                <div class="specialist-tags">
                    <span class="tag tag-${spec.level.toLowerCase()}">${spec.level}</span>
                    ${spec.tags.map(tag => `<span class="tag tag-${tag.toLowerCase()}">${tag}</span>`).join('')}
                </div>
                <p class="specialist-bio">${spec.bio}</p>
                <div class="skill-meter">
                    <div class="meter-label">Скиллы</div>
                    <div class="meter-bar">
                        <div class="meter-fill" style="width: ${spec.skills}%"></div>
                    </div>
                </div>
            </div>
        `;
        
        container.appendChild(card);
    });
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всех функций
    initParticles();
    initParallax();
    initScrollReveal();
    initMobileMenu();
    initInteractiveCards();
    initFormEffects();
    loadSpecialists();
    
    // Запускаем счетчики при скролле до них
    const heroStats = document.querySelector('.hero-stats');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });
    
    if (heroStats) {
        observer.observe(heroStats);
    }
    
    // Плавная прокрутка
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Добавляем классы для анимации появления
    document.querySelectorAll('.cyber-section').forEach((section, index) => {
        section.classList.add('scroll-reveal');
        section.style.animationDelay = `${index * 0.2}s`;
    });
});
// Обработка кнопок тарифов
function initPricingButtons() {
    const pricingButtons = document.querySelectorAll('.pricing-btn');
    
    pricingButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const card = this.closest('.pricing-card');
            const planName = card.querySelector('.pricing-name').textContent;
            const price = card.querySelector('.price-amount').textContent;
            
            // Анимация клика
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 200);
            
            // Показываем сообщение или перенаправляем
            alert(`Вы выбрали тариф "${planName.trim()}" за ${price}₽\n\nС вами свяжется наш менеджер для обсуждения деталей.`);
            
            // В реальном проекте здесь будет отправка на сервер
            // Можно добавить отправку в Google Analytics или Yandex.Metrika
            console.log(`Выбран тариф: ${planName}, Цена: ${price}₽`);
        });
    });
    
    // Добавляем анимацию при наведении на карточки
    const pricingCards = document.querySelectorAll('.pricing-card');
    pricingCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            const priceElement = card.querySelector('.price-amount');
            if (priceElement) {
                priceElement.style.animation = 'goldGlow 1s infinite alternate';
            }
        });
        
        card.addEventListener('mouseleave', () => {
            const priceElement = card.querySelector('.price-amount');
            if (priceElement) {
                priceElement.style.animation = '';
            }
        });
    });
}