require "middleman"
require "middleman/rack"
require "middleman-core/rack"
require "rspec"
require "capybara/rspec"
require "capybara/webkit"

module MiddlemanApplication
  extend RSpec::SharedContext

  main_app = ::Middleman::Application.new do ||
    config[:watcher_disable] = true
  end

  let(:app) do
    main_app
  end

  Capybara.javascript_driver = :webkit
  Capybara.default_max_wait_time = 5

  Capybara::Webkit.configure do |config|
    config.block_unknown_urls
  end

  Capybara.app = ::Middleman::Rack.new(main_app).to_app do
    set :root, File.expand_path(File.join(File.dirname(__FILE__), ".."))
    set :environment, :development
    set :show_exceptions, false
  end
end

RSpec.configure do |config|
  config.color = true
end