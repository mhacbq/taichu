export const trackEvent = (eventName, eventData = {}) => {
  // 模拟埋点发送
  const payload = {
    event: eventName,
    data: eventData,
    timestamp: new Date().toISOString(),
    url: window.location.href,
    userAgent: navigator.userAgent
  };
  
  // 在实际项目中，这里会调用后端的埋点接口或第三方统计SDK
  console.log('[Tracker]', payload);
  
  // 示例：如果存在全局的统计对象，可以调用它
  // if (window._hmt) {
  //   window._hmt.push(['_trackEvent', eventData.category, eventName, eventData.label, eventData.value]);
  // }
};

export const trackPageView = (pageName) => {
  trackEvent('page_view', { page: pageName });
};

export const trackClick = (elementName, extraData = {}) => {
  trackEvent('click', { element: elementName, ...extraData });
};

export const trackSubmit = (formName, isSuccess, extraData = {}) => {
  trackEvent('submit', { form: formName, success: isSuccess, ...extraData });
};

export const trackError = (errorType, errorMessage, extraData = {}) => {
  trackEvent('error', { type: errorType, message: errorMessage, ...extraData });
};
