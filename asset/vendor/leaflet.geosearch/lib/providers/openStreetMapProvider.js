'use strict';

Object.defineProperty(exports, "__esModule", {
  value: true
});

var _nodentRuntime = require('nodent-runtime');

var _nodentRuntime2 = _interopRequireDefault(_nodentRuntime);

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

var _provider = require('./provider');

var _provider2 = _interopRequireDefault(_provider);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

var Provider = function (_BaseProvider) {
  _inherits(Provider, _BaseProvider);

  function Provider() {
    _classCallCheck(this, Provider);

    return _possibleConstructorReturn(this, (Provider.__proto__ || Object.getPrototypeOf(Provider)).apply(this, arguments));
  }

  _createClass(Provider, [{
    key: 'endpoint',
    value: function endpoint() {
      var _ref = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
          query = _ref.query;

      var params = this.options.params;


      var paramString = this.getParamString(_extends({}, params, {
        format: 'json',
        q: query
      }));

      return 'https://nominatim.openstreetmap.org/search?' + paramString;
    }
  }, {
    key: 'endpointReverse',
    value: function endpointReverse() {
      var _ref2 = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {},
          data = _ref2.data;

      var params = this.options.params;


      var paramString = this.getParamString(_extends({}, params, {
        format: 'json',
        // eslint-disable-next-line camelcase
        osm_id: data.raw.osm_id,
        // eslint-disable-next-line camelcase
        osm_type: this.translateOsmType(data.raw.osm_type)
      }));

      return 'https://nominatim.openstreetmap.org/reverse?' + paramString;
    }
  }, {
    key: 'parse',
    value: function parse(_ref3) {
      var data = _ref3.data;

      return data.map(function (r) {
        return {
          x: r.lon,
          y: r.lat,
          label: r.display_name,
          bounds: [[parseFloat(r.boundingbox[0]), parseFloat(r.boundingbox[2])], // s, w
          [parseFloat(r.boundingbox[1]), parseFloat(r.boundingbox[3])]],
          raw: r
        };
      });
    }
  }, {
    key: 'search',
    value: function search(_ref4) {
      return new Promise(function ($return, $error) {
        var query, data, protocol, url, request, json;
        query = _ref4.query, data = _ref4.data;

        protocol = ~location.protocol.indexOf('http') ? location.protocol : 'https:';

        url = data ? this.endpointReverse({ data: data, protocol: protocol }) : this.endpoint({ query: query, protocol: protocol });

        return fetch(url).then(function ($await_1) {
          request = $await_1;
          return request.json().then(function ($await_2) {
            json = $await_2;
            return $return(this.parse({ data: data ? [json] : json }));
          }.$asyncbind(this, $error), $error);
        }.$asyncbind(this, $error), $error);
      }.$asyncbind(this));
    }
  }, {
    key: 'translateOsmType',
    value: function translateOsmType(type) {
      if (type === 'node') return 'N';
      if (type === 'way') return 'W';
      if (type === 'relation') return 'R';
      return ''; // Unknown
    }
  }]);

  return Provider;
}(_provider2.default);

exports.default = Provider;